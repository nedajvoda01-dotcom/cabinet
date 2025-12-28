<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Controllers;

use Cabinet\Backend\Application\Bus\CommandBus;
use Cabinet\Backend\Application\Commands\Access\ApproveAccessCommand;
use Cabinet\Backend\Application\Commands\Access\RequestAccessCommand;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;

final class AccessController
{
    public function __construct(
        private readonly CommandBus $commandBus
    ) {
    }

    public function requestAccess(Request $request): ApiResponse
    {
        $body = json_decode($request->body(), true);

        if (!isset($body['requestedBy'])) {
            return new ApiResponse(['error' => 'requestedBy is required'], 400);
        }

        $command = new RequestAccessCommand($body['requestedBy']);
        $result = $this->commandBus->dispatch($command);

        if ($result->isFailure()) {
            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], 400);
        }

        return new ApiResponse(['accessRequestId' => $result->value()], 200);
    }

    public function approveAccess(Request $request): ApiResponse
    {
        $body = json_decode($request->body(), true);

        if (!isset($body['accessRequestId']) || !isset($body['resolverUserId'])) {
            return new ApiResponse(['error' => 'accessRequestId and resolverUserId are required'], 400);
        }

        $command = new ApproveAccessCommand($body['accessRequestId'], $body['resolverUserId']);
        $result = $this->commandBus->dispatch($command);

        if ($result->isFailure()) {
            $statusCode = match ($result->error()->code()->value) {
                'not_found' => 404,
                'invalid_state' => 409,
                default => 400
            };

            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], $statusCode);
        }

        return new ApiResponse(['userId' => $result->value()], 200);
    }
}
