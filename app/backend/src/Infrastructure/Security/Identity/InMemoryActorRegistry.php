<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Identity;

use Cabinet\Contracts\ActorType;
use Cabinet\Contracts\HierarchyRole;
use Cabinet\Contracts\Scope;

final class InMemoryActorRegistry
{
    /** @var array<string, ResolvedActor> */
    private array $actors;

    public function __construct()
    {
        $this->actors = [
            'user:user-123' => new ResolvedActor(
                'user-123',
                ActorType::USER,
                HierarchyRole::USER,
                [Scope::fromString('security.echo')->value()],
                ['key-1' => 'secret-key-user-123']
            ),
            'user:admin-1' => new ResolvedActor(
                'admin-1',
                ActorType::USER,
                HierarchyRole::ADMIN,
                [Scope::fromString('security.echo')->value()],
                ['key-2' => 'admin-secret']
            ),
            'user:limited' => new ResolvedActor(
                'limited',
                ActorType::USER,
                HierarchyRole::USER,
                [],
                ['key-3' => 'limited-secret']
            ),
        ];
    }

    public function find(ActorType $type, string $actorId): ?ResolvedActor
    {
        $key = sprintf('%s:%s', $type->value, $actorId);

        return $this->actors[$key] ?? null;
    }
}
