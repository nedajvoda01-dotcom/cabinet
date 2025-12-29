<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Http\Request;

final class RequestIdTest extends TestCase
{
    public function testProvidedRequestIdIsReturned(): void
    {
        $kernel = $this->createKernel();

        $response = $kernel->handle(new Request('GET', '/health', ['X-Request-Id' => 'abc-123']));

        $this->assertEquals('abc-123', $response->header('X-Request-Id'));
    }

    public function testRequestIdIsGeneratedWhenMissing(): void
    {
        $kernel = $this->createKernel();

        $response = $kernel->handle(new Request('GET', '/health'));
        $requestId = $response->header('X-Request-Id');

        $this->assertTrue(is_string($requestId));
        $this->assertNotEmpty((string) $requestId);
    }
}
