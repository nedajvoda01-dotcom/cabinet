<?php
// backend/src/Adapters/Fakes/FakePhotoProcessorAdapter.php

declare(strict_types=1);

namespace App\Adapters\Fakes;

use App\Adapters\Ports\PhotoProcessorPort;

final class FakePhotoProcessorAdapter implements PhotoProcessorPort
{
    public function __construct(private ?string $fixturesDir = null) {}

    public function maskPhoto(string $rawUrl, array $maskParams = [], ?string $idempotencyKey = null): array
    {
        $payload = $this->fixture('mask_response.example.json');
        $result = $payload['results'][0] ?? [];

        return [
            'masked_url' => (string)($result['masked_url'] ?? $rawUrl),
            'meta' => ['order_no' => $result['order_no'] ?? 0] + (array)($maskParams['meta'] ?? []),
        ];
    }

    public function health(): array
    {
        return ['status' => 'ok', 'source' => 'fake-photo-api'];
    }

    /** @return array<string, mixed> */
    private function fixture(string $file): array
    {
        $base = $this->fixturesDir ?? dirname(__DIR__, 3) . '/external/photo-api/fixtures';
        $path = $base . '/' . $file;

        if (!is_file($path)) {
            return [];
        }

        $json = json_decode((string)file_get_contents($path), true);
        return is_array($json) ? $json : [];
    }
}
