<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Http\Request;

final class VersionEndpointTest extends TestCase
{
    public function testVersionDefaults(): void
    {
        putenv('CABINET_VERSION');
        putenv('CABINET_COMMIT');

        $kernel = $this->createKernel();

        $response = $kernel->handle(new Request('GET', '/version'));
        $payload = json_decode($response->body(), true);

        $this->assertEquals(200, $response->statusCode());
        $this->assertEquals('cabinet-backend', $payload['name'] ?? null);
        $this->assertEquals('dev', $payload['version'] ?? null);
        $this->assertTrue(array_key_exists('commit', $payload));
        $this->assertEquals(null, $payload['commit']);
    }
}
