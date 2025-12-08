<?php
namespace Modules\Photos;

class PhotosController
{
    private PhotosService $service;

    public function __construct(PhotosService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
