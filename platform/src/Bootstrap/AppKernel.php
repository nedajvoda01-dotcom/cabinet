<?php

declare(strict_types=1);

namespace Cabinet\Backend\Bootstrap;

use Cabinet\Backend\Http\Kernel\HttpKernel;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;

final class AppKernel
{
    private Container $container;

    public function __construct(Container $container)
    {
        $this->container = $container;
    }

    public function handle(Request $request): ApiResponse
    {
        return $this->container->httpKernel()->handle($request);
    }

    public function httpKernel(): HttpKernel
    {
        return $this->container->httpKernel();
    }
}
