<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Bootstrap\Clock;
use Cabinet\Backend\Bootstrap\Config;
use Cabinet\Backend\Bootstrap\Container;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Contracts\HierarchyRole;
use RuntimeException;

final class ErrorHandlingTest extends TestCase
{
    public function testUnhandledExceptionResultsInInternalError(): void
    {
        $config = Config::fromEnvironment();
        $clock = new Clock();
        $container = new Container($config, $clock);
        $container->router()->get('/boom', static function (): ApiResponse {
            throw new RuntimeException('boom');
        });
        $container->requirementsMap()->add(
            'GET',
            '/boom',
            new RouteRequirements(false, false, false, false, [], HierarchyRole::USER, 0)
        );

        $response = $container->httpKernel()->handle(new Request('GET', '/boom'));
        $payload = json_decode($response->body(), true);

        $this->assertEquals(500, $response->statusCode());
        $this->assertEquals(['error' => 'internal_error'], $payload);
    }
}
