<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Http\Security\SecurityContext;

final class ScopeStep
{
    public function enforce(Request $request, RouteRequirements $requirements): void
    {
        if (!$requirements->requiresAuth()) {
            return;
        }

        $context = $request->attribute('security_context');
        if (!$context instanceof SecurityContext) {
            throw new SecurityViolation('authentication_failed');
        }

        $required = array_map(static fn ($scope) => $scope->value(), $requirements->requiredScopes());
        foreach ($required as $scope) {
            if (!in_array($scope, $context->scopes(), true)) {
                throw new SecurityViolation('scope_missing');
            }
        }
    }
}
