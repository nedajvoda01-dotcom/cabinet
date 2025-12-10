<?php
// backend/src/Adapters/Fakes/FakeParserAdapter.php

declare(strict_types=1);

namespace App\Adapters\Fakes;

use App\Adapters\Ports\ParserPort;

final class FakeParserAdapter implements ParserPort
{
    public function __construct(private ?string $fixturesDir = null) {}

    public function normalizePush(array $push): array
    {
        $fixture = $this->fixture('parse_response.example.json');
        $payload = $fixture ?: $push;

        return [
            'ad' => (array)($payload['data'] ?? []),
            'photos' => (array)($payload['photos'] ?? []),
        ];
    }

    public function ingestRawPhotos(array $photoUrls, int $cardDraftId): array
    {
        $out = [];
        $order = 0;

        foreach ($photoUrls as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }

            $order++;

            $out[] = [
                'order' => $order,
                'raw_key' => "raw/{$cardDraftId}/{$order}.jpg",
                'raw_url' => (string)$url,
            ];
        }

        return $out;
    }

    public function poll(int $limit = 20): array
    {
        $fixture = $this->fixture('parse_response.example.json');
        return $fixture ? [$fixture] : [];
    }

    public function ack(string $externalId, array $meta = [], ?string $idempotencyKey = null): void
    {
        // no-op for fake adapter
    }

    /** @return array<string, mixed> */
    private function fixture(string $file): array
    {
        $base = $this->fixturesDir ?? dirname(__DIR__, 3) . '/external/parser/fixtures';
        $path = $base . '/' . $file;

        if (!is_file($path)) {
            return [];
        }

        $json = json_decode((string)file_get_contents($path), true);
        return is_array($json) ? $json : [];
    }
}
