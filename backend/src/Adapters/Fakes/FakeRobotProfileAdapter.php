<?php
// backend/src/Adapters/Fakes/FakeRobotProfileAdapter.php

declare(strict_types=1);

namespace App\Adapters\Fakes;

use App\Adapters\Ports\RobotProfilePort;

final class FakeRobotProfileAdapter implements RobotProfilePort
{
    public function __construct(private ?string $fixturesDir = null) {}

    public function allocateProfile(array $cardSnapshot): array
    {
        $fixture = $this->fixture('profile_response.example.json');
        return $fixture ?: [
            'correlation_id' => 'c-dolphin-fake',
            'status' => 'ok',
            'profile' => ['id' => 'profile-fake', 'name' => 'fake', 'status' => 'ready'],
        ];
    }

    public function startProfile(string $profileId): array
    {
        $fixture = $this->fixture('session_response.example.json');
        return $fixture ?: [
            'correlation_id' => 'c-dolphin-fake',
            'status' => 'ok',
            'session_id' => 'session-fake',
        ];
    }

    public function stopProfile(string $profileId): void
    {
        // no-op
    }

    public function health(): array
    {
        return ['status' => 'ok', 'source' => 'fake-dolphin'];
    }

    /** @return array<string, mixed> */
    private function fixture(string $file): array
    {
        $base = $this->fixturesDir ?? dirname(__DIR__, 3) . '/external/dolphin/fixtures';
        $path = $base . '/' . $file;
        if (!is_file($path)) {
            return [];
        }
        $json = json_decode((string)file_get_contents($path), true);
        return is_array($json) ? $json : [];
    }
}
