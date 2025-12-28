<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Requirements;

use Cabinet\Contracts\HierarchyRole;
use Cabinet\Contracts\Scope;

final class RouteRequirements
{
    /** @param Scope[] $requiredScopes */
    public function __construct(
        private readonly bool $requiresAuth,
        private readonly bool $requiresNonce,
        private readonly bool $requiresSignature,
        private readonly bool $requiresEncryption,
        private readonly array $requiredScopes,
        private readonly HierarchyRole $minRole,
        private readonly int $rateLimitPerMinute
    ) {
    }

    public function requiresAuth(): bool
    {
        return $this->requiresAuth;
    }

    public function requiresNonce(): bool
    {
        return $this->requiresNonce;
    }

    public function requiresSignature(): bool
    {
        return $this->requiresSignature;
    }

    public function requiresEncryption(): bool
    {
        return $this->requiresEncryption;
    }

    /** @return Scope[] */
    public function requiredScopes(): array
    {
        return $this->requiredScopes;
    }

    public function minRole(): HierarchyRole
    {
        return $this->minRole;
    }

    public function rateLimitPerMinute(): int
    {
        return $this->rateLimitPerMinute;
    }
}
