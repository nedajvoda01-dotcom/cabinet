<?php
namespace Modules\Users;

class UsersController
{
    private UsersService $service;

    public function __construct(UsersService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
