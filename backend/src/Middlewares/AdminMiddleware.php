<?php
// backend/src/Middlewares/AdminMiddleware.php

namespace Backend\Middlewares;

final class AdminMiddleware
{
    private RoleMiddleware $role;

    public function __construct()
    {
        $this->role = new RoleMiddleware(['admin', 'superadmin']);
    }

    public function __invoke($req, callable $next)
    {
        return ($this->role)($req, $next);
    }
}
