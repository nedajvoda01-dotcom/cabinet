<?php
// tests/unit/backend/contractsValidator.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\ContractValidator;
use App\Adapters\AdapterException;

define('APP_ROOT', realpath(__DIR__ . '/../../..'));

final class ContractsValidatorTest extends TestCase
{
    public function test_valid_parse_request_passes(): void
    {
        $validator = new ContractValidator();
        $schema = $this->schemaPath('parser/contracts/parse_request.json');

        $validator->validate([
            'source' => 'auto_ru',
            'url' => 'https://example.test/car',
            'correlation_id' => 'corr-123456',
        ], $schema);

        $this->assertTrue(true);
    }

    public function test_missing_required_field_triggers_contract_error(): void
    {
        $validator = new ContractValidator();
        $schema = $this->schemaPath('parser/contracts/parse_request.json');

        $this->expectException(AdapterException::class);
        $this->expectExceptionMessage('missing required property');

        $validator->validate([
            'source' => 'auto_ru',
        ], $schema);
    }

    private function schemaPath(string $relative): string
    {
        return APP_ROOT . '/external/' . $relative;
    }
}
