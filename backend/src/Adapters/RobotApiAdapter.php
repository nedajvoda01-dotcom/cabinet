<?php
// backend/src/Adapters/RobotApiAdapter.php

namespace App\Adapters;

use App\Adapters\HttpClient;
use App\Adapters\Ports\RobotPort;
use App\Utils\ContractValidator;

final class RobotApiAdapter implements RobotPort
{
    public function __construct(
        private HttpClient $http,
        private ContractValidator $contracts,
        private string $baseUrl,
        private string $apiKey
    ) {}

    /**
     * Start robot session inside Dolphin profile.
     * Returns {session_id, meta}
     */
    public function start(array $profile, ?string $idempotencyKey = null): array
    {
        $url = rtrim($this->baseUrl, '/') . "/sessions/start";
        $payload = [
            'profile' => $profile,
        ];

        $this->contracts->validate($payload, $this->contractPath('publish_request.json'));

        $resp = $this->http->post($url, $payload, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);
        $this->http->assertOk($resp, "robot");

        $this->contracts->validate((array)$resp['body'], $this->contractPath('publish_response.json'));

        if (!is_array($resp['body']) || empty($resp['body']['session_id'])) {
            throw new AdapterException("Robot start contract broken", "robot_contract", true);
        }
        return $resp['body'];
    }

    /**
     * Publish card to Avito via robot.
     * Input: PublishRequest (card + media + mapped avito payload)
     * Output: PublishResult {avito_item_id, avito_status, meta}
     */
    public function publish(string $sessionId, array $avitoPayload, ?string $idempotencyKey = null): array
    {
        $url = rtrim($this->baseUrl, '/') . "/publish";
        $payload = [
            'session_id' => $sessionId,
            'payload' => $avitoPayload,
        ];

        $this->contracts->validate($payload, $this->contractPath('publish_request.json'));

        $resp = $this->http->post($url, $payload, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);
        $this->http->assertOk($resp, "robot");

        $this->contracts->validate((array)$resp['body'], $this->contractPath('publish_response.json'));

        if (!is_array($resp['body']) || empty($resp['body']['avito_item_id'])) {
            throw new AdapterException("Robot publish contract broken", "robot_publish_contract", true, ['body'=>$resp['body']]);
        }
        return $resp['body'];
    }

    /**
     * Poll publish status by robot job / avito id.
     */
    public function pollStatus(string $avitoItemId, ?string $idempotencyKey = null): array
    {
        $url = rtrim($this->baseUrl, '/') . "/publish/{$avitoItemId}/status";
        $resp = $this->http->get($url, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);
        $this->http->assertOk($resp, "robot");

        $this->contracts->validate((array)$resp['body'], $this->contractPath('run_status_response.json'));

        return is_array($resp['body']) ? $resp['body'] : [];
    }

    public function stop(string $sessionId, ?string $idempotencyKey = null): void
    {
        $url = rtrim($this->baseUrl, '/') . "/sessions/{$sessionId}/stop";
        $resp = $this->http->post($url, null, [
            'Authorization' => "Bearer {$this->apiKey}",
        ], $idempotencyKey);
        $this->http->assertOk($resp, "robot");
    }

    public function health(): array
    {
        $url = rtrim($this->baseUrl, '/') . "/health";
        $resp = $this->http->get($url);
        $this->http->assertOk($resp, "robot");

        return is_array($resp['body']) ? $resp['body'] : ['ok'=>true];
    }

    private function contractPath(string $file): string
    {
        return dirname(__DIR__, 3) . "/external/robot/contracts/{$file}";
    }
}
