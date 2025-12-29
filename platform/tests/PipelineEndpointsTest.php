<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests;

use Cabinet\Backend\Bootstrap\Clock;
use Cabinet\Backend\Bootstrap\Config;
use Cabinet\Backend\Bootstrap\Container;
use Cabinet\Backend\Http\Request;
use Cabinet\Contracts\Scope;

final class PipelineEndpointsTest extends TestCase
{
    public function testTickEndpointWithoutSecurityHeaders(): void
    {
        $container = new Container(Config::fromEnvironment(), new Clock());
        $request = new Request('POST', '/tasks/task-123/tick', [], '{}');
        $kernel = $container->httpKernel();

        $response = $kernel->handle($request);

        $this->assertTrue($response->statusCode() === 403, 'Should return 403 without security headers');
    }

    public function testOutputsEndpointWithoutSecurityHeaders(): void
    {
        $container = new Container(Config::fromEnvironment(), new Clock());
        $request = new Request('GET', '/tasks/task-123/outputs', [], '');
        $kernel = $container->httpKernel();

        $response = $kernel->handle($request);

        $this->assertTrue($response->statusCode() === 403, 'Should return 403 without security headers');
    }

    public function testTickEndpointWithValidRequest(): void
    {
        $container = new Container(Config::fromEnvironment(), new Clock());
        
        // Register user with proper scopes using registerUser method
        $actorRegistry = $container->actorRegistry();
        $actorRegistry->registerUser('user-123', 'secret-key', [
            Scope::fromString('tasks.create')->value(),
            Scope::fromString('tasks.tick')->value()
        ]);

        // Create task
        $createRequest = $this->buildSignedRequest(
            'POST',
            '/tasks/create',
            '{"idempotencyKey":"test-key-1"}',
            'nonce-' . time() . '-1',
            'user:user-123',
            'key-1',
            'secret-key',
            'trace-' . time()
        );

        $kernel = $container->httpKernel();
        $createResponse = $kernel->handle($createRequest);

        $this->assertTrue($createResponse->statusCode() === 201, 'Task creation should succeed');
        $createBody = json_decode($createResponse->body(), true);
        $taskId = $createBody['taskId'];

        // Now tick the task
        $tickRequest = $this->buildSignedRequest(
            'POST',
            '/tasks/' . $taskId . '/tick',
            '',
            'nonce-' . time() . '-2',
            'user:user-123',
            'key-1',
            'secret-key',
            'trace-' . time()
        );

        $tickResponse = $kernel->handle($tickRequest);

        $this->assertTrue($tickResponse->statusCode() === 200, 'Tick should succeed');
        $tickBody = json_decode($tickResponse->body(), true);
        $this->assertTrue($tickBody['status'] === 'advanced', 'Should advance pipeline');
        $this->assertTrue($tickBody['completed_stage'] === 'parse', 'Should complete parse stage');
    }

    public function testOutputsEndpointReturnsOutputs(): void
    {
        $container = new Container(Config::fromEnvironment(), new Clock());
        $actorRegistry = $container->actorRegistry();
        $actorRegistry->registerUser('user-456', 'secret-key-456', [
            Scope::fromString('tasks.create')->value(),
            Scope::fromString('tasks.tick')->value(),
            Scope::fromString('tasks.read')->value()
        ]);

        // Create task
        $createRequest = $this->buildSignedRequest(
            'POST',
            '/tasks/create',
            '{"idempotencyKey":"test-key-2"}',
            'nonce-' . time() . '-3',
            'user:user-456',
            'key-1',
            'secret-key-456',
            'trace-' . time()
        );

        $kernel = $container->httpKernel();
        $createResponse = $kernel->handle($createRequest);
        $createBody = json_decode($createResponse->body(), true);
        $taskId = $createBody['taskId'];

        // Tick once to generate output
        $tickRequest = $this->buildSignedRequest(
            'POST',
            '/tasks/' . $taskId . '/tick',
            '',
            'nonce-' . time() . '-4',
            'user:user-456',
            'key-1',
            'secret-key-456',
            'trace-' . time()
        );
        $kernel->handle($tickRequest);

        // Get outputs
        $outputsRequest = $this->buildSignedRequest(
            'GET',
            '/tasks/' . $taskId . '/outputs',
            '',
            'nonce-' . time() . '-5',
            'user:user-456',
            'key-1',
            'secret-key-456',
            'trace-' . time()
        );

        $outputsResponse = $kernel->handle($outputsRequest);

        $this->assertTrue($outputsResponse->statusCode() === 200, 'Outputs endpoint should succeed');
        $outputsBody = json_decode($outputsResponse->body(), true);
        $this->assertTrue(isset($outputsBody['outputs']), 'Should have outputs key');
        $this->assertTrue(isset($outputsBody['outputs']['parse']), 'Should have parse output');
        $this->assertTrue($outputsBody['outputs']['parse']['payload']['source'] === 'demo', 'Parse output should match demo');
    }
}
