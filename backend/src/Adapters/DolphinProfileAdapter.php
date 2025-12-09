<?php
// backend/src/Adapters/DolphinProfileAdapter.php

namespace App\Adapters;

use App\Adapters\HttpClient;
use App\Adapters\Ports\RobotProfilePort;

final class DolphinProfileAdapter implements RobotProfilePort
{
    public function __construct(
        private HttpClient $http,
        private string $baseUrl,
        private string $apiKey
    ) {}

    public function allocateProfile(array $cardSnapshot): array
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/allocate";

        $resp = $this->http->post($url, [
            'card' => $cardSnapshot,
        ], [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);

        $this->http->assertOk($resp, "dolphin");
        if (!is_array($resp['body']) || empty($resp['body']['profile_id'])) {
            throw new AdapterException("Dolphin allocate contract broken", "dolphin_contract", true);
        }

        return $resp['body'];
    }

    public function startProfile(string $profileId): array
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/{$profileId}/start";
        $resp = $this->http->post($url, null, [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);
        $this->http->assertOk($resp, "dolphin");

        return is_array($resp['body']) ? $resp['body'] : [];
    }

    public function stopProfile(string $profileId): void
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/{$profileId}/stop";
        $resp = $this->http->post($url, null, [
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
}
