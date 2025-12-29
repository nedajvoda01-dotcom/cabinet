<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Controllers;

use Cabinet\Backend\Bootstrap\Config;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;

final class VersionController
{
    private Config $config;

    public function __construct(Config $config)
    {
        $this->config = $config;
    }

    public function version(Request $request): ApiResponse
    {
        return new ApiResponse([
            'name' => 'cabinet-backend',
            'version' => $this->config->version(),
            'commit' => $this->config->commit(),
        ]);
    }
}
