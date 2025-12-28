<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Http\Request;

final class ReadinessEndpointTest extends TestCase
{
    public function testReadinessEndpoint(): void
    {
        $kernel = $this->createKernel();

        $response = $kernel->handle(new Request('GET', '/readiness'));
        $payload = json_decode($response->body(), true);

        $this->assertEquals(200, $response->statusCode());
        $this->assertEquals(['status' => 'ready'], $payload);
    }
}
