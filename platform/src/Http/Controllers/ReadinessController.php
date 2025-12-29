<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Controllers;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;

final class ReadinessController
{
    public function readiness(Request $request): ApiResponse
    {
        return new ApiResponse(['status' => 'ready']);
    }
}
