<?php
// backend/src/Adapters/ParserAdapter.php

namespace App\Adapters;

final class ParserAdapter
{
    public function __construct(
        private HttpClient $http,
        private S3Adapter $s3,
        private string $baseUrl,
        private string $apiKey
    ) {}

    /**
     * Push payload from auto-parser.ru to core.
     * Contract: ParserPush { ad: AutoRuAd, photos: [url...] }
     * Returns normalized payload for ParserModule.
     */
    public function normalizePush(array $push): array
    {
        if (!isset($push['ad']) || !is_array($push['ad'])) {
            throw new AdapterException("ParserPush invalid: missing ad", "parser_contract", false);
        }
        $photos = $push['photos'] ?? [];
        if (!is_array($photos)) $photos = [];

        return ['ad' => $push['ad'], 'photos' => $photos];
    }

    /**
     * Poll mode: fetch ads from parser API if used.
     * Optional for MVP but included as max Spec capability.
     */
    public function poll(int $limit = 20): array
    {
        $url = rtrim($this->baseUrl, '/') . "/poll?limit={$limit}";
        $resp = $this->http->get($url, [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);
        $this->http->assertOk($resp, "parser");

        if (!is_array($resp['body'])) {
            throw new AdapterException("ParserPoll invalid JSON", "parser_poll_contract", true);
        }

        return $resp['body'];
    }

    public function ack(string $externalId, array $meta = []): void
    {
        $url = rtrim($this->baseUrl, '/') . "/ack";
        $resp = $this->http->post($url, [
            'external_id' => $externalId,
            'meta' => $meta,
        ], [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);
        $this->http->assertOk($resp, "parser");
    }

    // -------- helpers

    public function downloadBinary(string $url): string
    {
        $resp = $this->http->get($url);
        if ($resp['status'] >= 400) {
            throw new AdapterException("Photo download failed: {$url}", "parser_photo_download", true, ['url'=>$url,'status'=>$resp['status']]);
        }
        return (string)$resp['raw'];
    }

    public function uploadRaw(string $key, string $binary, string $extension): string
    {
        $this->s3->putObject($key, $binary, "image/{$extension}");

        return $this->s3->publicUrl($key);
    }

    public function publicUrl(string $key): string
    {
        return $this->s3->publicUrl($key);
    }

    public function guessExt(string $url): ?string
    {
        $p = parse_url($url, PHP_URL_PATH);
        if (!$p) return null;
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        return $ext ?: null;
    }
}
