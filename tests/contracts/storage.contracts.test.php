<?php
// tests/contracts/storage.contracts.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\ContractValidator;
use App\Adapters\Fakes\FakeStorageAdapter;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(dirname(__DIR__, 1) . '/..'));
}

final class StorageContractsTest extends TestCase
{
    private ContractValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContractValidator();
    }

    public function test_storage_contracts_and_fake_listing(): void
    {
        $putRequest = $this->fixture('external/storage/fixtures/put_request.example.json');
        $putResponse = $this->fixture('external/storage/fixtures/put_response.example.json');
        $listResponse = $this->fixture('external/storage/fixtures/list_response.example.json');
        $presignResponse = $this->fixture('external/storage/fixtures/get_presign_response.example.json');

        $this->validator->validate($putRequest, APP_ROOT . '/external/storage/contracts/put_request.json');
        $this->validator->validate($putResponse, APP_ROOT . '/external/storage/contracts/put_response.json');
        $this->validator->validate($listResponse, APP_ROOT . '/external/storage/contracts/list_response.json');
        $this->validator->validate($presignResponse, APP_ROOT . '/external/storage/contracts/get_presign_response.json');

        $adapter = new FakeStorageAdapter();
        $adapter->putObject('masked/a6/0.jpg', 'bin', 'image/jpeg');

        $listed = $adapter->listPrefix('masked/a6/');
        $url = $adapter->presignGet('masked/a6/0.jpg');

        $this->assertNotEmpty($listed);
        $this->assertStringContainsString('masked/a6/0.jpg', $url);
    }

    /** @return array<string, mixed> */
    private function fixture(string $path): array
    {
        $json = json_decode((string)file_get_contents(APP_ROOT . '/' . $path), true);
        return is_array($json) ? $json : [];
    }
}
