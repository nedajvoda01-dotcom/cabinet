<?php
// tests/unit/backend/httpClient.idempotency.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Adapters\HttpClient;

final class HttpClientIdempotencyTest extends TestCase
{
    public function testIdempotencyHeaderIsInjected(): void
    {
        $captured = [];
        $client = new HttpClient(10, 2, [500], function(array $request) use (&$captured) {
            $captured = $request;
            return [
                'status' => 200,
                'headers' => [],
                'body' => [],
                'raw' => '{}',
            ];
        });

        $client->post('http://example.local/publish', ['foo' => 'bar'], ['X-Test' => '1'], 'idem-123');

        $this->assertSame('idem-123', $captured['headers']['Idempotency-Key'] ?? null);
        $this->assertSame('1', $captured['headers']['X-Test'] ?? null);
        $this->assertSame('POST', $captured['method']);
    }
}
