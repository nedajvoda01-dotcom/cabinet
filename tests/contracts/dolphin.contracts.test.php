<?php
// tests/contracts/dolphin.contracts.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\ContractValidator;
use App\Adapters\Fakes\FakeRobotProfileAdapter;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(dirname(__DIR__, 1) . '/..'));
}

final class DolphinContractsTest extends TestCase
{
    private ContractValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContractValidator();
    }

    public function test_profile_and_session_contracts_and_fake_mapping(): void
    {
        $profileRequest = $this->fixture('external/dolphin/fixtures/profile_request.example.json');
        $profileResponse = $this->fixture('external/dolphin/fixtures/profile_response.example.json');
        $sessionResponse = $this->fixture('external/dolphin/fixtures/session_response.example.json');

        $this->validator->validate($profileRequest, APP_ROOT . '/external/dolphin/contracts/profile_request.json');
        $this->validator->validate($profileResponse, APP_ROOT . '/external/dolphin/contracts/profile_response.json');
        $this->validator->validate($sessionResponse, APP_ROOT . '/external/dolphin/contracts/session_response.json');

        $adapter = new FakeRobotProfileAdapter();
        $allocated = $adapter->allocateProfile([]);
        $started = $adapter->startProfile((string)($allocated['profile']['id'] ?? 'profile-0'));

        $this->assertSame($profileResponse['profile']['id'], $allocated['profile']['id']);
        $this->assertSame($sessionResponse['session_id'], $started['session_id']);
    }

    /** @return array<string, mixed> */
    private function fixture(string $path): array
    {
        $json = json_decode((string)file_get_contents(APP_ROOT . '/' . $path), true);
        return is_array($json) ? $json : [];
    }
}
