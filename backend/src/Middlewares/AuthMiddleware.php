<?php
// backend/src/Middlewares/AuthMiddleware.php

namespace Backend\Middlewares;

use Backend\Modules\Auth\AuthService;
use Backend\Modules\Users\UsersService;

/**
 * Проверяет access token.
 *
 * Ожидаемый контракт:
 * - AuthService::validateAccessToken(string $token): array|null
 *   возвращает ['user_id'=>int, 'roles'=>string[], 'exp'=>int, ...]
 * - UsersService::getById(int $id): array|null (с полем is_blocked)
 *
 * Если у тебя другие имена методов — меняешь ТОЛЬКО их вызов.
 */
final class AuthMiddleware
{
    public function __construct(
        private AuthService $auth,
        private UsersService $users
    ) {}

    /**
     * @param mixed $req  Request object (adapt if needed)
     * @param callable $next
     * @return mixed
     */
    public function __invoke($req, callable $next)
    {
        $token = $this->extractBearer($req);

        if (!$token) {
            return $this->unauthorized("Missing bearer token");
        }

        $claims = $this->auth->validateAccessToken($token);
        if (!$claims || empty($claims['user_id'])) {
            return $this->unauthorized("Invalid or expired token");
        }

        $userId = (int)$claims['user_id'];
        $user = $this->users->getById($userId);

        if (!$user) {
            return $this->unauthorized("User not found");
        }
        if (!empty($user['is_blocked'])) {
            return $this->forbidden("User is blocked");
        }

        // кладём в request context: req->user / req['user']
        $this->attachUser($req, $user, $claims);

        return $next($req);
    }

    // ---------------- helpers ----------------

    private function extractBearer($req): ?string
    {
        // adapt if needed: depending on your Request implementation
        $header = null;

        if (is_array($req) && isset($req['headers'])) {
            $header = $req['headers']['authorization'] ?? $req['headers']['Authorization'] ?? null;
        } elseif (method_exists($req, 'getHeader')) {
            $header = $req->getHeader('Authorization');
        } elseif (method_exists($req, 'header')) {
            $header = $req->header('Authorization');
        } else {
            $header = $_SERVER['HTTP_AUTHORIZATION'] ?? null;
        }

        if (is_array($header)) $header = $header[0] ?? null;
        if (!$header || !is_string($header)) return null;

        if (stripos($header, 'Bearer ') !== 0) return null;
        return trim(substr($header, 7));
    }

    private function attachUser(&$req, array $user, array $claims): void
    {
        $ctx = [
            'user' => $user,
            'auth' => $claims,
            'roles' => $claims['roles'] ?? ($user['roles'] ?? []),
        ];

        if (is_array($req)) {
            $req['context'] = array_merge($req['context'] ?? [], $ctx);
            return;
        }
        if (property_exists($req, 'context')) {
            $req->context = array_merge($req->context ?? [], $ctx);
            return;
        }
        if (method_exists($req, 'setAttribute')) {
            $req->setAttribute('context', $ctx);
            return;
        }
        // fallback: nothing to do
    }

    private function unauthorized(string $message)
    {
        // adapt if your framework uses Response objects
        http_response_code(401);
        return ['error' => 'unauthorized', 'message' => $message];
    }

    private function forbidden(string $message)
    {
        http_response_code(403);
        return ['error' => 'forbidden', 'message' => $message];
    }
}
