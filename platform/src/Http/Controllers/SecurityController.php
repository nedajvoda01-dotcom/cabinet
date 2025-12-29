<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Controllers;

use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;

final class SecurityController
{
    public function echo(Request $request): ApiResponse
    {
        return new ApiResponse([
            'status' => 'secure_echo',
            'body' => $request->body(),
        ]);
    }

    public function encryptedEcho(Request $request): ApiResponse
    {
        return new ApiResponse([
            'status' => 'secure_encrypted_echo',
            'body' => $request->body(),
        ]);
    }
}
