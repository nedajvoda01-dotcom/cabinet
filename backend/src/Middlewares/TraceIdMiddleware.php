<?php
declare(strict_types=1);

namespace Backend\Middlewares;

use Backend\Application\Contracts\TraceContext;

final class TraceIdMiddleware
{
    public function __invoke($req, callable $next)
    {
        $traceId = $this->extractTraceId($req) ?? TraceContext::generateTraceId();
        $context = TraceContext::fromString($traceId);
        TraceContext::setCurrent($context);

        $req = $this->attachTraceId($req, $traceId);
        header('X-Trace-Id: ' . $traceId);

        return $next($req);
    }

    private function extractTraceId($req): ?string
    {
        if (is_array($req)) {
            return $req['context']['traceId'] ?? $req['headers']['x-trace-id'] ?? null;
        }

        if (property_exists($req, 'context') && is_array($req->context)) {
            return $req->context['traceId'] ?? null;
        }

        if (method_exists($req, 'getHeader')) {
            $header = $req->getHeader('X-Trace-Id');
            if (is_string($header)) {
                return $header;
            }
        }

        return null;
    }

    private function attachTraceId($req, string $traceId)
    {
        if (is_array($req)) {
            $req['context'] = array_merge($req['context'] ?? [], ['traceId' => $traceId]);
            return $req;
        }

        if (property_exists($req, 'context')) {
            $req->context = array_merge($req->context ?? [], ['traceId' => $traceId]);
            return $req;
        }

        if (method_exists($req, 'setAttribute')) {
            $req->setAttribute('context', array_merge((array)($req->getAttribute('context') ?? []), ['traceId' => $traceId]));
            return $req;
        }

        return $req;
    }
}
