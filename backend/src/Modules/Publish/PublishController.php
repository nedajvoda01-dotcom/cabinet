<?php
namespace Modules\Publish;

class PublishController
{
    private PublishService $service;

    public function __construct(PublishService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
