<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Kernel;

use Cabinet\Backend\Bootstrap\Config;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;
use Cabinet\Backend\Http\Routing\Router;
use Cabinet\Backend\Http\Security\Pipeline\SecurityPipelineMiddleware;
use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;
use Cabinet\Backend\Http\Security\Requirements\EndpointRequirementsResolver;
use Cabinet\Backend\Infrastructure\Observability\Logging\StructuredLogger;
use Cabinet\Contracts\ErrorKind;
use RuntimeException;
use Throwable;

final class HttpKernel
{
    private Router $router;

    private StructuredLogger $logger;

    private Config $config;

    private SecurityPipelineMiddleware $securityPipeline;

    private EndpointRequirementsResolver $requirementsResolver;

    public function __construct(
        Router $router,
        StructuredLogger $logger,
        Config $config,
        SecurityPipelineMiddleware $securityPipeline,
        EndpointRequirementsResolver $requirementsResolver
    ) {
        $this->router = $router;
        $this->logger = $logger;
        $this->config = $config;
        $this->securityPipeline = $securityPipeline;
        $this->requirementsResolver = $requirementsResolver;
    }

    public function handle(Request $request): ApiResponse
    {
        $requestId = $this->resolveRequestId($request);
        $request->withRequestId($requestId);
        $traceId = $this->resolveTraceId($requestId, $request);
        $request->withTraceId($traceId);

        $startTime = microtime(true);
        $this->logger->info('request_start', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'trace_id' => $traceId,
        ]);

        try {
            $handler = $this->router->match($request);

            if ($handler === null) {
                $response = new ApiResponse(['error' => 'not_found'], 404);
            } else {
                $requirements = $this->requirementsResolver->resolve($request);
                $routeId = sprintf('%s %s', $request->method(), $request->path());

                if ($requirements === null) {
                    $response = $this->securityDenied($request, $routeId, 'missing_requirements', $traceId);
                } else {
                    $response = $this->securityPipeline->handle(
                        $request,
                        $requirements,
                        function (Request $incoming) use ($handler): ApiResponse {
                            $result = $handler($incoming);
                            if (!$result instanceof ApiResponse) {
                                throw new RuntimeException('Invalid response from handler');
                            }

                            return $result;
                        },
                        $routeId,
                        $traceId
                    );
                }
            }
        } catch (Throwable $throwable) {
            $response = new ApiResponse(['error' => 'internal_error'], 500);
            $this->logger->error('request_error', $this->errorContext($request, $requestId, $throwable));
        }

        $response = $response->withHeader('X-Request-Id', $requestId);

        $durationMs = (microtime(true) - $startTime) * 1000;
        $this->logger->info('request_end', [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'status_code' => $response->statusCode(),
            'duration_ms' => round($durationMs, 3),
            'trace_id' => $traceId,
        ]);

        return $response;
    }

    private function resolveRequestId(Request $request): string
    {
        $incoming = $request->header('x-request-id');

        if ($incoming !== null) {
            $sanitized = $this->sanitizeRequestId($incoming);
            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        return bin2hex(random_bytes(16));
    }

    private function resolveTraceId(string $requestId, Request $request): string
    {
        $incoming = $request->header(ProtocolHeaders::TRACE);
        if ($incoming !== null) {
            $sanitized = $this->sanitizeRequestId($incoming);
            if ($sanitized !== '') {
                return $sanitized;
            }
        }

        return $requestId;
    }

    private function sanitizeRequestId(string $requestId): string
    {
        $trimmed = substr($requestId, 0, 128);

        return preg_replace('/[^A-Za-z0-9\-_.]/', '', $trimmed) ?? '';
    }

    private function securityDenied(Request $request, string $routeId, string $code, string $traceId): ApiResponse
    {
        $this->logger->error('security_denied', [
            'route' => $routeId,
            'request_id' => $request->requestId(),
            'trace_id' => $traceId,
            'method' => $request->method(),
            'path' => $request->path(),
            'code' => $code,
        ]);

        return new ApiResponse([
            'error' => [
                'kind' => ErrorKind::SECURITY_DENIED->value,
                'code' => $code,
            ],
        ], 403);
    }

    private function errorContext(Request $request, string $requestId, Throwable $throwable): array
    {
        $context = [
            'request_id' => $requestId,
            'method' => $request->method(),
            'path' => $request->path(),
            'error' => $throwable->getMessage(),
        ];

        if ($this->config->environment() !== 'prod') {
            $context['trace'] = $throwable->getTraceAsString();
        }

        return $context;
    }
}
