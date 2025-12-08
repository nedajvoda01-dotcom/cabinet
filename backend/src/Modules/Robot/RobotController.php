<?php
namespace Modules\Robot;

class RobotController
{
    private RobotService $service;

    public function __construct(RobotService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
