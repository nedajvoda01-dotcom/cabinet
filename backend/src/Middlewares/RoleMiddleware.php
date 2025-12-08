<?php
// backend/src/Middlewares/RoleMiddleware.php

namespace Backend\Middlewares;

/**
 * Используется как:
 *  new RoleMiddleware(['admin'])
 * или
 *  new RoleMiddleware(['operator','admin'])
 */
final class RoleMiddleware
{
    /**
     * @param string[] $allowed
     */
    public function __construct(private array $allowed) {}

    public function __invoke($req, callable $next)
    {
        $roles = $this->extractRoles($req);

        foreach ($roles as $r) {
            if (in_array($r, $this->allowed, true)) {
                return $next($req);
            }
        }

        return $this->forbidden("Insufficient role");
    }

    private function extractRoles($req): array
    {
        if (is_array($req)) {
            return (array)($req['context']['roles'] ?? []);
        }
        if (property_exists($req, 'context') && is_array($req->context)) {
            return (array)($req->context['roles'] ?? []);
        }
        if (method_exists($req, 'getAttribute')) {
            $ctx = $req->getAttribute('context');
            if (is_array($ctx)) return (array)($ctx['roles'] ?? []);
        }
        return [];
    }

    private function forbidden(string $message)
    {
        http_response_code(403);
        return ['error' => 'forbidden', 'message' => $message];
    }
}
