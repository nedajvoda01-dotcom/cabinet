<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Handlers;

use Cabinet\Backend\Application\Bus\Command;
use Cabinet\Backend\Application\Bus\CommandHandler;
use Cabinet\Backend\Application\Commands\Tasks\CreateTaskCommand;
use Cabinet\Backend\Application\Ports\IdGenerator;
use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineState;
use Cabinet\Backend\Domain\Tasks\Task;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryTaskRepository;
use Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories\TasksRepository;

/**
 * @implements CommandHandler<string>
 */
final class CreateTaskHandler implements CommandHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly PipelineStateRepository $pipelineStateRepository,
        private readonly IdGenerator $idGenerator
    ) {
    }

    public function handle(Command $command): Result
    {
        if (!$command instanceof CreateTaskCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        // Check idempotency
        $existingTask = $this->taskRepository->findByActorAndIdempotencyKey(
            $command->actorId(),
            $command->idempotencyKey()
        );

        if ($existingTask !== null) {
            return Result::success($existingTask->id()->toString());
        }

        // Create new task
        $taskId = TaskId::fromString($this->idGenerator->generate());
        $task = Task::create($taskId);
        $this->taskRepository->save($task);

        // Store idempotency mapping
        if ($this->taskRepository instanceof InMemoryTaskRepository || $this->taskRepository instanceof TasksRepository) {
            $this->taskRepository->storeIdempotencyKey(
                $command->actorId(),
                $command->idempotencyKey(),
                $taskId
            );
        }

        // Create initial pipeline state
        $jobId = JobId::fromString($taskId->toString());
        $pipelineState = PipelineState::create($jobId);
        $this->pipelineStateRepository->save($pipelineState);

        return Result::success($taskId->toString());
    }
}
