<?php
// cabinet/backend/src/Adapters/DolphinProfileAdapter.php

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

    /**
     * Allocate Dolphin profile for card snapshot.
     * Optional idempotencyKey is passed to HttpClient for safe retries.
     */
    public function allocateProfile(array $cardSnapshot, ?string $idempotencyKey = null): array
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/allocate";

        // Валидируем фактический request payload, который уходит во внешку
        $payload = ['card' => $cardSnapshot];
        $this->contracts->validate($payload, $this->contractPath('profile_request.json'));

        $resp = $this->http->post($url, $payload, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);

        $this->http->assertOk($resp, "dolphin");

        // Fail-fast response validation
        $this->contracts->validate((array)$resp['body'], $this->contractPath('profile_response.json'));

        if (!is_array($resp['body']) || empty($resp['body']['profile_id'])) {
            throw new AdapterException("Dolphin allocate contract broken", "dolphin_contract", true);
        }

        return $resp['body'];
    }

    /**
     * Start Dolphin profile session.
     * В mainline body не отправлялся (profile_id только в path),
     * поэтому request-валидацию не делаем, чтобы не менять поведение.
     */
    public function startProfile(string $profileId, ?string $idempotencyKey = null): array
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/{$profileId}/start";

        $resp = $this->http->post($url, null, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);

        $this->http->assertOk($resp, "dolphin");

        // Fail-fast response validation
        $this->contracts->validate((array)$resp['body'], $this->contractPath('session_response.json'));

        return is_array($resp['body']) ? $resp['body'] : [];
    }

    /**
     * Stop Dolphin profile session.
     * Аналогично startProfile — body не отправляем.
     */
    public function stopProfile(string $profileId, ?string $idempotencyKey = null): void
    {
        $url = rtrim($this->baseUrl, '/') . "/profiles/{$profileId}/stop";

        $resp = $this->http->post($url, null, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);

        $this->http->assertOk($resp, "dolphin");
    }

    public function health(): array
    {
        $url = rtrim($this->baseUrl, '/') . "/health";
        $resp = $this->http->get($url);

        $this->http->assertOk($resp, "dolphin");

        return is_array($resp['body']) ? $resp['body'] : ['ok' => true];
    }

    private function contractPath(string $file): string
    {
        return dirname(__DIR__, 3) . "/external/dolphin/contracts/{$file}";
    }
}
