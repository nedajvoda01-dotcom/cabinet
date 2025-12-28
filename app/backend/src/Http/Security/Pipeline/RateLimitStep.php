<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Http\Security\SecurityContext;
use Cabinet\Backend\Infrastructure\Security\AttackProtection\RateLimiter;

final class RateLimitStep
{
    public function __construct(private readonly RateLimiter $rateLimiter)
    {
    }

    public function enforce(Request $request, RouteRequirements $requirements, string $routeId): void
    {
        if (!$requirements->requiresAuth()) {
            return;
        }

        $context = $request->attribute('security_context');
        if (!$context instanceof SecurityContext) {
            throw new SecurityViolation('authentication_failed');
        }

        if (!$this->rateLimiter->allow($context->actorId(), $routeId, $requirements->rateLimitPerMinute())) {
            throw new SecurityViolation('rate_limited');
        }
    }
}
