<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Requirements;

use Cabinet\Contracts\HierarchyRole;
use Cabinet\Contracts\Scope;

final class RouteRequirementsMap
{
    /** @var array<string, RouteRequirements> */
    private array $map;

    public function __construct()
    {
        $this->map = [
            'GET /health' => new RouteRequirements(false, false, false, false, [], HierarchyRole::USER, 0),
            'GET /readiness' => new RouteRequirements(false, false, false, false, [], HierarchyRole::USER, 0),
            'GET /version' => new RouteRequirements(false, false, false, false, [], HierarchyRole::USER, 0),
            'POST /security/echo' => new RouteRequirements(true, true, true, false, [Scope::fromString('security.echo')], HierarchyRole::USER, 2),
            'POST /security/encrypted-echo' => new RouteRequirements(true, true, true, true, [Scope::fromString('security.echo')], HierarchyRole::USER, 2),
            'POST /security/admin-echo' => new RouteRequirements(true, true, true, false, [Scope::fromString('security.echo')], HierarchyRole::ADMIN, 2),
            // Application layer endpoints
            'POST /access/request' => new RouteRequirements(false, false, false, false, [], HierarchyRole::USER, 5),
            'POST /admin/access/approve' => new RouteRequirements(true, true, true, false, [Scope::fromString('admin.access.approve')], HierarchyRole::ADMIN, 5),
            'POST /tasks/create' => new RouteRequirements(true, true, true, false, [Scope::fromString('tasks.create')], HierarchyRole::USER, 10),
        ];
    }

    public function resolve(string $method, string $path): ?RouteRequirements
    {
        $key = sprintf('%s %s', strtoupper($method), $path);

        return $this->map[$key] ?? null;
    }

    public function add(string $method, string $path, RouteRequirements $requirements): void
    {
        $key = sprintf('%s %s', strtoupper($method), $path);
        $this->map[$key] = $requirements;
    }
}
