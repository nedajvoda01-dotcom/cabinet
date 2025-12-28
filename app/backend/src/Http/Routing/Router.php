<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Routing;

use Cabinet\Backend\Http\Request;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    public function get(string $path, callable $handler): void
    {
        $this->routes['GET'][$path] = $handler;
    }

    public function post(string $path, callable $handler): void
    {
        $this->routes['POST'][$path] = $handler;
    }

    public function match(Request $request): ?callable
    {
        $methodRoutes = $this->routes[$request->method()] ?? [];

        return $methodRoutes[$request->path()] ?? null;
    }
}
