<?php
namespace Modules\Admin;

class AdminController
{
    private AdminService $service;

    public function __construct(AdminService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
