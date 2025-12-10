<?php
// tests/contracts/parser.contracts.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Utils\ContractValidator;
use App\Adapters\ParserAdapter;
use App\Adapters\HttpClient;
use App\Adapters\S3Adapter;

if (!defined('APP_ROOT')) {
    define('APP_ROOT', realpath(dirname(__DIR__, 1) . '/..'));
}

final class ParserContractsTest extends TestCase
{
    private ContractValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new ContractValidator();
    }

    public function test_parse_response_fixture_matches_contract_and_normalizes(): void
    {
        $fixture = $this->fixture('external/parser/fixtures/parse_response.example.json');
        $this->validator->validate($fixture, APP_ROOT . '/external/parser/contracts/parse_response.json');

        $adapter = new ParserAdapter(
            $this->createMock(HttpClient::class),
            $this->createMock(S3Adapter::class),
            $this->validator,
            'http://parser',
            'key'
        );

        $normalized = $adapter->normalizePush($fixture);

        $this->assertSame($fixture['data'], $normalized['ad']);
        $this->assertCount(count($fixture['photos']), $normalized['photos']);
        $this->assertSame(0, $normalized['photos'][0]['order_no'] ?? $normalized['photos'][0]['order'] ?? 0);
    }

    /** @return array<string, mixed> */
    private function fixture(string $path): array
    {
        $json = json_decode((string)file_get_contents(APP_ROOT . '/' . $path), true);
        return is_array($json) ? $json : [];
    }
}
