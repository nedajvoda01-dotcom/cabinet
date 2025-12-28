<?php

declare(strict_types=1);

namespace Cabinet\Backend\Http\Controllers;

use Cabinet\Backend\Application\Bus\CommandBus;
use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand;
use Cabinet\Backend\Application\Queries\GetTaskOutputsQuery;
use Cabinet\Backend\Http\Request;
use Cabinet\Backend\Http\Responses\ApiResponse;
use Cabinet\Backend\Http\Security\SecurityContext;

final class TasksController
{
    public function __construct(
        private readonly CommandBus $commandBus,
        private readonly ?GetTaskOutputsQuery $getTaskOutputsQuery = null,
        private readonly ?\Cabinet\Backend\Application\Queries\ListTasksQuery $listTasksQuery = null,
        private readonly ?\Cabinet\Backend\Application\Queries\GetTaskDetailsQuery $getTaskDetailsQuery = null
    ) {
    }

    public function create(Request $request): ApiResponse
    {
        $body = json_decode($request->body(), true);

        if (!isset($body['idempotencyKey'])) {
            return new ApiResponse(['error' => 'idempotencyKey is required'], 400);
        }

        // Get actorId from SecurityContext, fallback to demo actor for development
        $context = $request->attribute('security_context');
        
        $actorId = 'demo-user'; // Default for demo/development
        if ($context instanceof SecurityContext) {
            $actorId = $context->actorId();
        }

        $command = new CreateTaskCommand($actorId, $body['idempotencyKey']);
        $result = $this->commandBus->dispatch($command);

        if ($result->isFailure()) {
            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], 400);
        }

        return new ApiResponse(['taskId' => $result->value()], 201);
    }

    public function tick(Request $request): ApiResponse
    {
        $taskId = $request->attribute('id');

        if (empty($taskId)) {
            return new ApiResponse(['error' => 'Task ID is required'], 400);
        }

        $command = new TickTaskCommand($taskId);
        $result = $this->commandBus->dispatch($command);

        if ($result->isFailure()) {
            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], 400);
        }

        return new ApiResponse($result->value(), 200);
    }

    public function outputs(Request $request): ApiResponse
    {
        $taskId = $request->attribute('id');

        if (empty($taskId)) {
            return new ApiResponse(['error' => 'Task ID is required'], 400);
        }

        if ($this->getTaskOutputsQuery === null) {
            return new ApiResponse(['error' => 'Query not available'], 500);
        }

        $result = $this->getTaskOutputsQuery->execute($taskId);

        if ($result->isFailure()) {
            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], 400);
        }

        return new ApiResponse($result->value(), 200);
    }

    public function list(Request $request): ApiResponse
    {
        if ($this->listTasksQuery === null) {
            return new ApiResponse(['error' => 'Query not available'], 500);
        }

        $result = $this->listTasksQuery->execute();

        if ($result->isFailure()) {
            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], 400);
        }

        return new ApiResponse($result->value(), 200);
    }

    public function details(Request $request): ApiResponse
    {
        $taskId = $request->attribute('id');

        if (empty($taskId)) {
            return new ApiResponse(['error' => 'Task ID is required'], 400);
        }

        if ($this->getTaskDetailsQuery === null) {
            return new ApiResponse(['error' => 'Query not available'], 500);
        }

        $result = $this->getTaskDetailsQuery->execute($taskId);

        if ($result->isFailure()) {
            $statusCode = match ($result->error()->code()->value) {
                'not_found' => 404,
                default => 400
            };

            return new ApiResponse([
                'error' => $result->error()->message(),
                'code' => $result->error()->code()->value
            ], $statusCode);
        }

        return new ApiResponse($result->value(), 200);
    }
}
