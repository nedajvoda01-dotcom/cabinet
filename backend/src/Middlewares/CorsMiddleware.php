<?php
// backend/src/Middlewares/CorsMiddleware.php

namespace Backend\Middlewares;

final class CorsMiddleware
{
    public function __construct(
        private string $allowOrigin = '*',
        private string $allowMethods = 'GET,POST,PATCH,DELETE,OPTIONS',
        private string $allowHeaders = 'Authorization,Content-Type,X-Requested-With'
    ) {}

    public function __invoke($req, callable $next)
    {
        header("Access-Control-Allow-Origin: {$this->allowOrigin}");
        header("Access-Control-Allow-Methods: {$this->allowMethods}");
        header("Access-Control-Allow-Headers: {$this->allowHeaders}");
        header("Access-Control-Allow-Credentials: true");

        // preflight
        $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if ($method === 'OPTIONS') {
            http_response_code(204);
            return '';
        }

        return $next($req);
    }
}
