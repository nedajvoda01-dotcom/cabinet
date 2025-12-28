<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Controllers;

use Cabinet\Backend\Application\Bus\CommandBus;
use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;
use Cabinet\Backend\Http\Security\SecurityContext;

final class TasksController
{
    public function __construct(
        private readonly CommandBus $commandBus
    ) {
    }

    public function create(Request $request): ApiResponse
    {
        $body = json_decode($request->body(), true);

        if (!isset($body['idempotencyKey'])) {
            return new ApiResponse(['error' => 'idempotencyKey is required'], 400);
        }

        // Get actorId from SecurityContext
        $context = $request->attribute('security_context');

        if (!$context instanceof SecurityContext) {
            return new ApiResponse(['error' => 'Actor not authenticated'], 401);
        }

        $command = new CreateTaskCommand($context->actorId(), $body['idempotencyKey']);
        $result = $this->commandBus->dispatch($command);

        if ($result->isFailure()) {
            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], 400);
        }

        return new ApiResponse(['taskId' => $result->value()], 201);
    }
}
