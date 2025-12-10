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
        // Fail-fast: валидируем входящий payload по контракту parser push
        $this->contracts->validate($push, $this->contractPath('parse_response.json'));

        if (!isset($push['ad']) || !is_array($push['ad'])) {
            throw new AdapterException("ParserPush invalid: missing ad", "parser_contract", false);
        }

        $photos = $push['photos'] ?? [];
        if (!is_array($photos)) {
            $photos = [];
        }

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

        // Fail-fast: валидируем ответ poll по контракту
        $this->contracts->validate($resp['body'], $this->contractPath('parse_response.json'));

        return $resp['body'];
    }

    public function ack(string $externalId, array $meta = [], ?string $idempotencyKey = null): void
    {
        $url = rtrim($this->baseUrl, '/') . "/ack";

        $payload = [
            'external_id' => $externalId,
            'meta' => $meta,
        ];

        // Если у ack есть контракт — можно включить здесь.
        // Сейчас не трогаем, чтобы не менять поведение вне явных схем.
        // $this->contracts->validate($payload, $this->contractPath('ack_request.json'));

        $resp = $this->http->post($url, $payload, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);

        $this->http->assertOk($resp, "parser");

        // Аналогично можно валидировать ответ, если контракт есть:
        // $this->contracts->validate((array)$resp['body'], $this->contractPath('ack_response.json'));
    }

    // -------- helpers

    public function downloadBinary(string $url): string
    {
        $resp = $this->http->get($url);

        if ($resp['status'] >= 400) {
            throw new AdapterException(
                "Photo download failed: {$url}",
                "parser_photo_download",
                true,
                ['url' => $url, 'status' => $resp['status']]
            );
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

    private function contractPath(string $file): string
    {
        return dirname(__DIR__, 3) . "/external/parser/contracts/{$file}";
    }
}
