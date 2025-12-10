<?php
// backend/src/Adapters/ParserAdapter.php

namespace App\Adapters;

use App\Adapters\Ports\ParserPort;
use App\Utils\ContractValidator;

final class ParserAdapter implements ParserPort
{
    public function __construct(
        private HttpClient $http,
        private S3Adapter $s3,
        private ContractValidator $contracts,
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
        $this->contracts->validate($push, $this->contractPath('parse_response.json'));

        if (!isset($push['ad']) || !is_array($push['ad'])) {
            throw new AdapterException("ParserPush invalid: missing ad", "parser_contract", false);
        }
        $photos = $push['photos'] ?? [];
        if (!is_array($photos)) $photos = [];

        return ['ad' => $push['ad'], 'photos' => $photos];
    }

    /**
     * Download raw photos and upload to S3 raw/.
     * Returns list of {raw_key, raw_url, order}.
     */
    public function ingestRawPhotos(array $photoUrls, int $cardDraftId): array
    {
        $out = [];
        $order = 0;

        foreach ($photoUrls as $url) {
            $order++;
            if (!is_string($url) || $url === '') continue;

            $bin = $this->downloadBinary($url);
            $ext = $this->guessExt($url) ?? 'jpg';

            $key = "raw/{$cardDraftId}/{$order}.{$ext}";
            $this->s3->putObject($key, $bin, "image/{$ext}");

            $out[] = [
                'order' => $order,
                'raw_key' => $key,
                'raw_url' => $this->s3->publicUrl($key),
            ];
        }

        return $out;
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

        $this->contracts->validate($resp['body'], $this->contractPath('parse_response.json'));

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

    private function downloadBinary(string $url): string
    {
        $resp = $this->http->get($url);
        if ($resp['status'] >= 400) {
            throw new AdapterException("Photo download failed: {$url}", "parser_photo_download", true, ['url'=>$url,'status'=>$resp['status']]);
        }
        return (string)$resp['raw'];
    }

    private function guessExt(string $url): ?string
    {
        $p = parse_url($url, PHP_URL_PATH);
        if (!$p) return null;
        $ext = strtolower(pathinfo($p, PATHINFO_EXTENSION));
        return $ext ?: null;
    }

    private function contractPath(string $file): string
    {
        return dirname(__DIR__, 3) . "/external/parser/contracts/{$file}";
    }
}
