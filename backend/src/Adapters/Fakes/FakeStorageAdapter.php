<?php
// backend/src/Adapters/Fakes/FakeStorageAdapter.php

declare(strict_types=1);

namespace App\Adapters\Fakes;

use App\Adapters\Ports\StoragePort;

final class FakeStorageAdapter implements StoragePort
{
    /** @var array<string, string> */
    private array $objects = [];

    public function __construct(private ?string $fixturesDir = null) {}

    public function putObject(string $key, string $binary, string $contentType = 'application/octet-stream'): void
    {
        $this->objects[$key] = $binary;
    }

    public function getObject(string $key): string
    {
        if (array_key_exists($key, $this->objects)) {
            return $this->objects[$key];
        }

        $payload = $this->fixture('put_request.example.json');
        return json_encode($payload) ?: '';
    }

    public function presignGet(string $key, int $expiresSec = 3600): string
    {
        $payload = $this->fixture('get_presign_response.example.json');
        return (string)($payload['url'] ?? "http://fake-storage/{$key}");
    }

    public function listPrefix(string $prefix): array
    {
        if ($this->objects) {
            return array_values(array_filter(array_keys($this->objects), fn(string $k) => str_starts_with($k, $prefix)));
        }

        $payload = $this->fixture('list_response.example.json');
        return (array)($payload['items'] ?? []);
    }

    public function publicUrl(string $key): string
    {
        return "http://fake-storage/{$key}";
    }

    /** @return array<string, mixed> */
    private function fixture(string $file): array
    {
        $base = $this->fixturesDir ?? dirname(__DIR__, 3) . '/external/storage/fixtures';
        $path = $base . '/' . $file;
        if (!is_file($path)) {
            return [];
        }
        $json = json_decode((string)file_get_contents($path), true);
        return is_array($json) ? $json : [];
    }
}
