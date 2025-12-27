<?php
// backend/src/Adapters/HttpClient.php

namespace App\Adapters;

final class HttpClient
{
    public function __construct(
        private int $timeoutSec = 30,
        private int $connectTimeoutSec = 5
    ) {}

    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function post(string $url, array|string|null $body = null, array $headers = []): array
    {
        return $this->request('POST', $url, $body, $headers);
    }

    public function put(string $url, array|string|null $body = null, array $headers = []): array
    {
        return $this->request('PUT', $url, $body, $headers);
    }

    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    /**
     * @return array{status:int, headers:array, body:mixed, raw:string}
     */
    public function request(string $method, string $url, array|string|null $body, array $headers = []): array
    {
        $ch = curl_init($url);
        if ($ch === false) {
            throw new AdapterException("curl_init failed", "curl_init_failed", true);
        }

        $outHeaders = [];
        $headerLines = [];
        foreach ($headers as $k => $v) $headerLines[] = "{$k}: {$v}";
        if (!empty($headerLines)) curl_setopt($ch, CURLOPT_HTTPHEADER, $headerLines);

        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $this->timeoutSec);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $this->connectTimeoutSec);

        if ($body !== null) {
            if (is_array($body)) {
                $json = json_encode($body, JSON_UNESCAPED_UNICODE);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $json);
                curl_setopt($ch, CURLOPT_HTTPHEADER, array_merge($headerLines, [
                    'Content-Type: application/json',
                ]));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
            }
        }

        curl_setopt($ch, CURLOPT_HEADERFUNCTION, function($ch, $header) use (&$outHeaders) {
            $len = strlen($header);
            $parts = explode(':', $header, 2);
            if (count($parts) === 2) {
                $outHeaders[strtolower(trim($parts[0]))] = trim($parts[1]);
            }
            return $len;
        });

        $raw = curl_exec($ch);
        if ($raw === false) {
            $err = curl_error($ch);
            curl_close($ch);
            throw new AdapterException("Network error: {$err}", "network_error", true);
        }

        $status = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $decoded = null;
        $ct = $outHeaders['content-type'] ?? '';
        if (str_contains($ct, 'application/json')) {
            $decoded = json_decode($raw, true);
        }

        return [
            'status' => $status,
            'headers' => $outHeaders,
            'raw' => $raw,
            'body' => $decoded ?? $raw,
        ];
    }

    public function assertOk(array $resp, string $service, array $retryOn = [500,502,503,504]): void
    {
        $st = $resp['status'];
        if ($st >= 200 && $st < 300) return;

        $retryable = in_array($st, $retryOn, true);
        $msg = is_array($resp['body']) ? json_encode($resp['body']) : (string)$resp['raw'];

        throw new AdapterException(
            "{$service} HTTP {$st}: {$msg}",
            "{$service}_http_{$st}",
            $retryable,
            ['status'=>$st, 'body'=>$resp['body']]
        );
    }
}
