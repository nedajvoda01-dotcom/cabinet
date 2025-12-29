<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Http\Security\SecurityContext;
use Cabinet\Contracts\HierarchyRole;

final class HierarchyStep
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

        $required = $requirements->minRole();
        if ($this->rank($context->role()) < $this->rank($required)) {
            throw new SecurityViolation('role_insufficient');
        }
    }

    private function rank(HierarchyRole $role): int
    {
        return match ($role) {
            HierarchyRole::USER => 1,
            HierarchyRole::ADMIN => 2,
            HierarchyRole::SUPER_ADMIN => 3,
        };
    }
}
