<?php
// backend/src/Adapters/HttpClient.php

namespace App\Adapters;

use RuntimeException;

final class HttpClient
{
    /** @var int[] */
    private array $retryableStatuses;

    /** @var callable|null */
    private $transport;

    public function __construct(
        private int $timeoutSec = 30,
        private int $connectTimeoutSec = 5,
        array $retryableStatuses = [500, 502, 503, 504],
        ?callable $transport = null
    ) {
        $this->retryableStatuses = $retryableStatuses;
        $this->transport = $transport;
    }

    public function get(string $url, array $headers = []): array
    {
        return $this->request('GET', $url, null, $headers);
    }

    public function post(string $url, array|string|null $body = null, array $headers = [], ?string $idempotencyKey = null): array
    {
        return $this->request('POST', $url, $body, $headers, $idempotencyKey);
    }

    public function put(string $url, array|string|null $body = null, array $headers = [], ?string $idempotencyKey = null): array
    {
        return $this->request('PUT', $url, $body, $headers, $idempotencyKey);
    }

    public function delete(string $url, array $headers = []): array
    {
        return $this->request('DELETE', $url, null, $headers);
    }

    /**
     * @return array{status:int, headers:array, body:mixed, raw:string, meta:array}
     */
    public function request(string $method, string $url, array|string|null $body, array $headers = [], ?string $idempotencyKey = null): array
    {
        $normalizedHeaders = $this->normalizeHeaders($headers, $idempotencyKey);
        $requestMeta = [
            'url' => $url,
            'method' => $method,
            'headers' => $normalizedHeaders,
        ];

        if ($this->transport) {
            $result = ($this->transport)([
                'method' => $method,
                'url' => $url,
                'headers' => $normalizedHeaders,
                'body' => $body,
            ]);

            if (!is_array($result)) {
                throw new RuntimeException('HttpClient transport must return response array');
            }

            return $result + ['meta' => $requestMeta];
        }

        $ch = curl_init($url);
        if ($ch === false) {
            throw new AdapterException("curl_init failed", "curl_init_failed", true, ['url' => $url]);
        }

        $outHeaders = [];
        $headerLines = [];
        foreach ($normalizedHeaders as $k => $v) $headerLines[] = "{$k}: {$v}";
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
            $errno = curl_errno($ch);
            curl_close($ch);
            $code = $errno === CURLE_OPERATION_TIMEDOUT ? 'timeout' : 'network_error';
            throw new AdapterException("Network error: {$err}", $code, true, ['url' => $url, 'errno' => $errno]);
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
            'meta' => $requestMeta,
        ];
    }

    public function assertOk(array $resp, string $service): void
    {
        $st = $resp['status'];
        if ($st >= 200 && $st < 300) return;

        $retryable = $this->isRetryableStatus($st);
        $msg = is_array($resp['body']) ? json_encode($resp['body']) : (string)$resp['raw'];

        throw new AdapterException(
            "{$service} HTTP {$st}: {$msg}",
            "{$service}_http_{$st}",
            $retryable,
            ['status'=>$st, 'body'=>$resp['body'], 'service'=>$service]
        );
    }

    private function normalizeHeaders(array $headers, ?string $idempotencyKey = null): array
    {
        if ($idempotencyKey) {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $headers;
    }

    private function isRetryableStatus(int $status): bool
    {
        return in_array($status, $this->retryableStatuses, true);
    }
}
