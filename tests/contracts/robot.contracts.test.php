<?php
// tests/contracts/robot.contracts.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\ContractValidator;
use App\Adapters\Fakes\FakeRobotApiAdapter;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(dirname(__DIR__, 1) . '/..'));
}

final class RobotContractsTest extends TestCase
{
    private ContractValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContractValidator();
    }

    public function test_publish_contracts_and_fake_mapping(): void
    {
        $publishRequest = $this->fixture('external/robot/fixtures/publish_request.example.json');
        $publishResponse = $this->fixture('external/robot/fixtures/publish_response.example.json');
        $runStatus = $this->fixture('external/robot/fixtures/run_status_response.example.json');

        $this->validator->validate($publishRequest, APP_ROOT . '/external/robot/contracts/publish_request.json');
        $this->validator->validate($publishResponse, APP_ROOT . '/external/robot/contracts/publish_response.json');
        $this->validator->validate($runStatus, APP_ROOT . '/external/robot/contracts/run_status_response.json');

        $adapter = new FakeRobotApiAdapter();
        $session = $adapter->start([]);
        $publish = $adapter->publish((string)($session['session_id'] ?? 'session-0'), []);
        $status = $adapter->pollStatus((string)($publish['run_id'] ?? 'run-0'));

        $this->assertSame($publishResponse['run_id'], $publish['run_id']);
        $this->assertSame($runStatus['status'], $status['status']);
    }

    /** @return array<string, mixed> */
    private function fixture(string $path): array
    {
        $json = json_decode((string)file_get_contents(APP_ROOT . '/' . $path), true);
        return is_array($json) ? $json : [];
    }
}
