<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;
use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Infrastructure\Observability\Logging\StructuredLogger;
use Cabinet\Contracts\ErrorKind;
use Throwable;

final class SecurityPipelineMiddleware
{
    public function __construct(
        private readonly AuthStep $authStep,
        private readonly NonceStep $nonceStep,
        private readonly SignatureStep $signatureStep,
        private readonly EncryptionStep $encryptionStep,
        private readonly ScopeStep $scopeStep,
        private readonly HierarchyStep $hierarchyStep,
        private readonly RateLimitStep $rateLimitStep,
        private readonly StructuredLogger $logger
    ) {
    }

    public function handle(Request $request, RouteRequirements $requirements, callable $next, string $routeId, string $traceId): ApiResponse
    {
        try {
            $this->authStep->enforce($request, $requirements);
            $nonce = $this->nonceStep->enforce($request, $requirements);
            $this->signatureStep->enforce($request, $requirements, $nonce, $traceId);
            $kid = $request->header(ProtocolHeaders::KEY_ID) ?? '';
            $this->encryptionStep->enforce($request, $requirements, $kid);
            $this->scopeStep->enforce($request, $requirements);
            $this->hierarchyStep->enforce($request, $requirements);
            $this->rateLimitStep->enforce($request, $requirements, $routeId);
        } catch (SecurityViolation $violation) {
            return $this->deny($request, $routeId, $violation->errorCode(), $traceId);
        } catch (Throwable $throwable) {
            return $this->deny($request, $routeId, 'security_denied', $traceId, $throwable);
        }

        return $next($request);
    }

    private function deny(Request $request, string $routeId, string $code, string $traceId, ?Throwable $throwable = null): ApiResponse
    {
        $context = [
            'event' => 'security_denied',
            'route' => $routeId,
            'request_id' => $request->requestId(),
            'trace_id' => $traceId,
            'method' => $request->method(),
            'path' => $request->path(),
            'code' => $code,
        ];

        $actor = $request->attribute('security_context');
        if ($actor !== null && method_exists($actor, 'actorId')) {
            $context['actor_id'] = $actor->actorId();
        }

        if ($throwable !== null) {
            $context['error'] = $throwable->getMessage();
        }

        $this->logger->error('security_denied', $context);

        return new ApiResponse([
            'error' => [
                'kind' => ErrorKind::SECURITY_DENIED->value,
                'code' => $code,
            ],
        ], 403);
    }
}
