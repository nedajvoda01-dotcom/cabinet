<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Security\Signatures;

use Cabinet\Backend\Http\Request;

final class StringToSignBuilder
{
    public function build(Request $request, string $nonce, string $kid, string $traceId): array
    {
        return [
            'method' => $request->method(),
            'path' => $request->path(),
            'trace_id' => $traceId,
            'nonce' => $nonce,
            'kid' => $kid,
            'body_sha256' => hash('sha256', $request->body()),
        ];
    }
}
