<?php
// tests/unit/backend/traceContext.test.php

declare(strict_types=1);

use Backend\Application\Contracts\Error;
use Backend\Application\Contracts\ErrorKind;
use Backend\Application\Contracts\TraceContext;
use Backend\Middlewares\TraceIdMiddleware;
use PHPUnit\Framework\TestCase;

final class TraceContextTest extends TestCase
{
    public function test_trace_id_middleware_sets_context(): void
    {
        $middleware = new TraceIdMiddleware();
        $result = $middleware([], function (array $req) {
            return $req;
        });

        $this->assertIsArray($result);
        $this->assertArrayHasKey('context', $result);
        $this->assertArrayHasKey('traceId', $result['context']);
        $this->assertNotEmpty($result['context']['traceId']);
        $this->assertSame($result['context']['traceId'], TraceContext::ensure()->traceId());
    }

    public function test_error_carries_trace_id(): void
    {
        $trace = TraceContext::fromString('trace-test');
        TraceContext::setCurrent($trace);

        $error = Error::fromMessage('forbidden', ErrorKind::AUTH, 'no access');

        $payload = $error->toArray();
        $this->assertSame('trace-test', $payload['traceId']);
        $this->assertSame('forbidden', $payload['code']);
        $this->assertSame(ErrorKind::AUTH->value, $payload['kind']);
    }
}
