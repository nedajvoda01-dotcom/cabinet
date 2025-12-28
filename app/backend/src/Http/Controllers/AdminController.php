<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Controllers;

use Cabinet\Backend\Application\Bus\CommandBus;
use Cabinet\Backend\Application\Commands\Admin\RetryJobCommand;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;

final class AdminController
{
    public function __construct(
        private readonly CommandBus $commandBus
    ) {
    }

    public function retryJob(Request $request): ApiResponse
    {
        $body = json_decode($request->body(), true);

        if (!isset($body['taskId'])) {
            return new ApiResponse(['error' => 'taskId is required'], 400);
        }

        $allowDlqOverride = $body['allowDlqOverride'] ?? false;
        $reason = $body['reason'] ?? null;

        $command = new RetryJobCommand($body['taskId'], $allowDlqOverride, $reason);
        $result = $this->commandBus->dispatch($command);

        if ($result->isFailure()) {
            $statusCode = match ($result->error()->code()->value) {
                'not_found' => 404,
                'permission_denied' => 403,
                'invalid_state' => 409,
                default => 400
            };

            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], $statusCode);
        }

        return new ApiResponse(['success' => true], 200);
    }
}
