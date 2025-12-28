<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Pipeline;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Security\Protocol\ProtocolHeaders;
use Cabinet\Backend\Http\Security\Requirements\RouteRequirements;
use Cabinet\Backend\Http\Security\SecurityContext;
use Cabinet\Backend\Infrastructure\Security\Identity\InMemoryActorRegistry;
use Cabinet\Contracts\ActorType;
use Cabinet\Contracts\HierarchyRole;

final class AuthStep
{
    private InMemoryActorRegistry $registry;

    public function __construct(InMemoryActorRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function enforce(Request $request, RouteRequirements $requirements): void
    {
        if (!$requirements->requiresAuth()) {
            return;
        }

        $header = $request->header(ProtocolHeaders::ACTOR);
        if ($header === null) {
            throw new SecurityViolation('missing_header');
        }

        if (!preg_match('/^(user|integration):(.+)$/', $header, $matches)) {
            throw new SecurityViolation('authentication_failed');
        }

        $type = $matches[1] === 'user' ? ActorType::USER : ActorType::INTEGRATION;
        $actorId = $matches[2];

        $actor = $this->registry->find($type, $actorId);
        if ($actor === null) {
            throw new SecurityViolation('authentication_failed');
        }

        $request->withAttribute(
            'security_context',
            new SecurityContext($actor->actorId(), $actor->actorType(), $actor->role(), $actor->scopes(), $actor->keys())
        );
    }

    public function roleForPublic(): HierarchyRole
    {
        return HierarchyRole::USER;
    }
}
