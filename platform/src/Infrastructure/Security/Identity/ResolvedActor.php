<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Identity;

use Cabinet\Contracts\ActorType;
use Cabinet\Contracts\HierarchyRole;

final class ResolvedActor
{
    /** @param array<string> $scopes */
    public function __construct(
        private readonly string $actorId,
        private readonly ActorType $actorType,
        private readonly HierarchyRole $role,
        private readonly array $scopes,
        /** @var array<string, string> */
        private readonly array $keys
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

    /** @return array<string, string> */
    public function keys(): array
    {
        return $this->keys;
    }
}
