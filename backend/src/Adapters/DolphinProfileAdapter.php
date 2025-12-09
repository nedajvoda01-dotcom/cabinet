<?php
// backend/src/Adapters/DolphinProfileAdapter.php

namespace App\Adapters;

use App\Adapters\HttpClient;
use App\Adapters\Ports\RobotProfilePort;
use App\Utils\ContractValidator;

final class DolphinProfileAdapter implements RobotProfilePort
{
    public function __construct(
        private HttpClient $http,
        private ContractValidator $contracts,
        private string $baseUrl,
        private string $apiKey
    ) {}

    public function allocateProfile(array $cardSnapshot): array
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/allocate";

        $this->contracts->validate($cardSnapshot, $this->contractPath('profile_request.json'));

        $resp = $this->http->post($url, [
            'card' => $cardSnapshot,
        ], [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);

        $this->http->assertOk($resp, "dolphin");
        $this->contracts->validate((array)$resp['body'], $this->contractPath('profile_response.json'));

        if (!is_array($resp['body']) || empty($resp['body']['profile_id'])) {
            throw new AdapterException("Dolphin allocate contract broken", "dolphin_contract", true);
        }

        return $resp['body'];
    }

    public function startProfile(string $profileId): array
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/{$profileId}/start";
        $payload = [
            'profile_id' => $profileId,
        ];

        $this->contracts->validate($payload, $this->contractPath('session_request.json'));

        $resp = $this->http->post($url, $payload, [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);
        $this->http->assertOk($resp, "dolphin");

        $this->contracts->validate((array)$resp['body'], $this->contractPath('session_response.json'));

        return is_array($resp['body']) ? $resp['body'] : [];
    }

    public function stopProfile(string $profileId): void
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/{$profileId}/stop";
        $payload = [
            'profile_id' => $profileId,
        ];

        $this->contracts->validate($payload, $this->contractPath('profile_request.json'));

        $resp = $this->http->post($url, $payload, [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);
        $this->http->assertOk($resp, "dolphin");
    }

    public function health(): array
    {
        $url = rtrim($this->baseUrl, '/') . "/health";
        $resp = $this->http->get($url);
        $this->http->assertOk($resp, "dolphin");

        return is_array($resp['body']) ? $resp['body'] : ['ok'=>true];
    }

    private function contractPath(string $file): string
    {
        return dirname(__DIR__, 3) . "/external/dolphin/contracts/{$file}";
    }
}
