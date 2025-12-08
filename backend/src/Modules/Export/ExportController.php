<?php
namespace Modules\Export;

class ExportController
{
    private ExportService $service;

    public function __construct(ExportService $service)
    {
        $this->service = $service;
    }

    // TODO: add controller actions
}
