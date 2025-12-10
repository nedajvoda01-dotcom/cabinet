<?php
// tests/contracts/photo_api.contracts.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\ContractValidator;
use App\Adapters\Fakes\FakePhotoProcessorAdapter;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(dirname(__DIR__, 1) . '/..'));
}

final class PhotoApiContractsTest extends TestCase
{
    private ContractValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContractValidator();
    }

    public function test_mask_contract_and_fake_mapping(): void
    {
        $request = $this->fixture('external/photo-api/fixtures/mask_request.example.json');
        $response = $this->fixture('external/photo-api/fixtures/mask_response.example.json');
        $status = $this->fixture('external/photo-api/fixtures/status_response.example.json');

        $this->validator->validate($request, APP_ROOT . '/external/photo-api/contracts/mask_request.json');
        $this->validator->validate($response, APP_ROOT . '/external/photo-api/contracts/mask_response.json');
        $this->validator->validate($status, APP_ROOT . '/external/photo-api/contracts/status_response.json');

        $adapter = new FakePhotoProcessorAdapter();
        $result = $adapter->maskPhoto('http://storage.local/raw/a6/0.jpg');

        $this->assertSame($response['results'][0]['masked_url'], $result['masked_url']);
        $this->assertSame($response['results'][0]['order_no'], $result['meta']['order_no']);
    }

    /** @return array<string, mixed> */
    private function fixture(string $path): array
    {
        $json = json_decode((string)file_get_contents(APP_ROOT . '/' . $path), true);
        return is_array($json) ? $json : [];
    }
}
