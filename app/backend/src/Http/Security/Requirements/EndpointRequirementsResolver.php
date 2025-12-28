<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Security\Requirements;

use Cabinet\Backend\Http\Request;

final class EndpointRequirementsResolver
{
    private RouteRequirementsMap $map;

    public function __construct(RouteRequirementsMap $map)
    {
        $this->map = $map;
    }

    public function resolve(Request $request): ?RouteRequirements
    {
        return $this->map->resolve($request->method(), $request->path());
    }
}
