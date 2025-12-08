<?php
namespace Modules\Auth;

class AuthController
{
    private AuthService $service;

    public function __construct(AuthService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
