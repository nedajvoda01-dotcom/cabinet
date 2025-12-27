<?php
// backend/src/Adapters/Fakes/FakeRobotApiAdapter.php

declare(strict_types=1);

namespace App\Adapters\Fakes;

use App\Adapters\Ports\RobotPort;

final class FakeRobotApiAdapter implements RobotPort
{
    public function __construct(private ?string $fixturesDir = null) {}

    public function start(array $profile, ?string $idempotencyKey = null): array
    {
        $resp = $this->fixture('publish_response.example.json');

        return [
            'session_id' => $resp['run_id'] ?? 'session-fake',
            'correlation_id' => $resp['correlation_id'] ?? 'c-fake',
            'status' => $resp['status'] ?? 'ok',
        ];
    }

    public function publish(string $sessionId, array $avitoPayload, ?string $idempotencyKey = null): array
    {
        $resp = $this->fixture('publish_response.example.json');

        return $resp ?: [
            'correlation_id' => 'c-fake',
            'status' => 'ok',
            'run_id' => 'run-fake',
        ];
    }

    public function pollStatus(string $avitoItemId): array
    {
        $resp = $this->fixture('run_status_response.example.json');

        return $resp ?: [
            'correlation_id' => 'c-fake',
            'run_id' => 'run-fake',
            'status' => 'queued',
        ];
    }

    public function stop(string $sessionId, ?string $idempotencyKey = null): void
    {
        // no-op
    }

    public function health(): array
    {
        return ['status' => 'ok', 'source' => 'fake-robot'];
    }

    /** @return array<string, mixed> */
    private function fixture(string $file): array
    {
        $base = $this->fixturesDir ?? dirname(__DIR__, 3) . '/external/robot/fixtures';
        $path = $base . '/' . $file;

        if (!is_file($path)) {
            return [];
        }

        $json = json_decode((string)file_get_contents($path), true);
        return is_array($json) ? $json : [];
    }
}
