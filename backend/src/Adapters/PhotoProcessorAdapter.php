<?php
// backend/src/Adapters/PhotoProcessorAdapter.php

namespace App\Adapters;

use App\Adapters\Ports\PhotoProcessorPort;
use App\Adapters\HttpClient;

final class PhotoProcessorAdapter implements PhotoProcessorPort
{
    public function __construct(
        private HttpClient $http,
        private string $baseUrl,
        private string $apiKey
    ) {}

    /**
     * Process one raw photo in Photo API.
     * Input contract: { raw_url, mask_params }
     * Output: { masked_url, meta }
     */
    public function maskPhoto(string $rawUrl, array $maskParams = []): array
    {
        $url = rtrim($this->baseUrl, '/') . "/process";

        $resp = $this->http->post($url, [
            'raw_url' => $rawUrl,
            'mask_params' => $maskParams,
        ], [
            'Authorization' => "Bearer {$this->apiKey}",
        ]);

        $this->http->assertOk($resp, "photo_api");

        if (!is_array($resp['body']) || empty($resp['body']['masked_url'])) {
            throw new AdapterException("Photo API contract broken", "photo_api_contract", true, ['body'=>$resp['body']]);
        }

        return [
            'masked_url' => (string)$resp['body']['masked_url'],
            'meta' => (array)($resp['body']['meta'] ?? []),
        ];
    }

    public function health(): array
    {
        $url = rtrim($this->baseUrl, '/') . "/health";
        $resp = $this->http->get($url);
        $this->http->assertOk($resp, "photo_api");

        return is_array($resp['body']) ? $resp['body'] : ['ok'=>true];
    }
}
