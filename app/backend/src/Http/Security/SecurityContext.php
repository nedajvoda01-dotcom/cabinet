<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security;

use Cabinet\Contracts\ActorType;
use Cabinet\Contracts\HierarchyRole;

final class SecurityContext
{
    /**
     * @param array<string> $scopes
     * @param array<string, string> $keys
     */
    public function __construct(
        private readonly string $actorId,
        private readonly ActorType $actorType,
        private readonly HierarchyRole $role,
        private readonly array $scopes,
        private readonly array $keys,
    ) {
    }

    public function actorId(): string
    {
        return $this->actorId;
    }

    public function actorType(): ActorType
    {
        return $this->actorType;
    }

    public function role(): HierarchyRole
    {
        return $this->role;
    }

    /** @return array<string> */
    public function scopes(): array
    {
        return $this->scopes;
    }

    public function keyForKid(string $kid): ?string
    {
        return $this->keys[$kid] ?? null;
    }
}
