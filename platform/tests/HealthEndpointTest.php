<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Http\Request;

final class HealthEndpointTest extends TestCase
{
    public function testHealthEndpoint(): void
    {
        $kernel = $this->createKernel();

        $response = $kernel->handle(new Request('GET', '/health'));
        $payload = json_decode($response->body(), true);

        $this->assertEquals(200, $response->statusCode());
        $this->assertEquals(['status' => 'ok'], $payload);
    }
}
