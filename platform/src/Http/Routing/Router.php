<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Routing;

use Cabinet\Backend\Http\Request;

final class Router
{
    /** @var array<string, array<string, callable>> */
    private array $routes = [];

    /** @var array<string, array<string, array<string, string>>> */
    private array $patternRoutes = [];

    public function get(string $path, callable $handler): void
    {
        if (strpos($path, '{') !== false) {
            $this->patternRoutes['GET'][$path] = ['handler' => $handler, 'pattern' => $this->compilePattern($path)];
        } else {
            $this->routes['GET'][$path] = $handler;
        }
    }

    public function post(string $path, callable $handler): void
    {
        if (strpos($path, '{') !== false) {
            $this->patternRoutes['POST'][$path] = ['handler' => $handler, 'pattern' => $this->compilePattern($path)];
        } else {
            $this->routes['POST'][$path] = $handler;
        }
    }

    public function match(Request $request): ?callable
    {
        $methodRoutes = $this->routes[$request->method()] ?? [];
        $exactMatch = $methodRoutes[$request->path()] ?? null;

        if ($exactMatch !== null) {
            return $exactMatch;
        }

        // Try pattern matching
        $patternRoutes = $this->patternRoutes[$request->method()] ?? [];
        foreach ($patternRoutes as $route) {
            if (preg_match($route['pattern'], $request->path(), $matches)) {
                // Extract parameters and add them to request
                array_shift($matches); // Remove full match
                if (!empty($matches)) {
                    // Store in request for controller access
                    foreach ($matches as $key => $value) {
                        if (!is_int($key)) {
                            $request->withAttribute($key, $value);
                        }
                    }
                }
                return $route['handler'];
            }
        }

        return null;
    }

    private function compilePattern(string $path): string
    {
        // Convert /tasks/{id}/tick to /tasks/([^/]+)/tick
        $pattern = preg_replace('/\{([^}]+)\}/', '(?P<$1>[^/]+)', $path);
        return '#^' . $pattern . '$#';
    }
}
