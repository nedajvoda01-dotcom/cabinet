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
            'POST /tasks/{id}/tick' => new RouteRequirements(true, true, true, false, [Scope::fromString('tasks.tick')], HierarchyRole::USER, 10),
            'GET /tasks/{id}/outputs' => new RouteRequirements(true, true, true, false, [Scope::fromString('tasks.read')], HierarchyRole::USER, 20),
            'POST /admin/pipeline/retry' => new RouteRequirements(true, true, true, false, [Scope::fromString('admin.pipeline.retry')], HierarchyRole::ADMIN, 5),
        ];
    }

    public function resolve(string $method, string $path): ?RouteRequirements
    {
        $key = sprintf('%s %s', strtoupper($method), $path);

        // Try exact match first
        if (isset($this->map[$key])) {
            return $this->map[$key];
        }

        // Try pattern matching
        foreach ($this->map as $pattern => $requirements) {
            if (strpos($pattern, '{') !== false) {
                $regex = $this->compilePattern($pattern);
                if (preg_match($regex, $key)) {
                    return $requirements;
                }
            }
        }

        return null;
    }

    private function compilePattern(string $key): string
    {
        // Convert "POST /tasks/{id}/tick" to regex
        $pattern = preg_replace('/\{[^}]+\}/', '[^/]+', $key);
        return '#^' . preg_quote($pattern, '#') . '$#';
    }

    public function add(string $method, string $path, RouteRequirements $requirements): void
    {
        $key = sprintf('%s %s', strtoupper($method), $path);
        $this->map[$key] = $requirements;
    }
}
