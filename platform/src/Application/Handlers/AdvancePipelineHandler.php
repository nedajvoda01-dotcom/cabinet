<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Handlers;

use Cabinet\Backend\Application\Bus\Command;
use Cabinet\Backend\Application\Bus\CommandHandler;
use Cabinet\Backend\Application\Commands\Pipeline\AdvancePipelineCommand;
use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Application\Shared\ApplicationError;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Tasks\TaskId;

/**
 * @implements CommandHandler<bool>
 */
final class AdvancePipelineHandler implements CommandHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly PipelineStateRepository $pipelineStateRepository
    ) {
    }

    public function handle(Command $command): Result
    {
        if (!$command instanceof AdvancePipelineCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $taskId = TaskId::fromString($command->taskId());
        $task = $this->taskRepository->findById($taskId);

        if ($task === null) {
            return Result::failure(ApplicationError::notFound('Task not found'));
        }

        $jobId = JobId::fromString($taskId->toString());
        $pipelineState = $this->pipelineStateRepository->findByJobId($jobId);

        if ($pipelineState === null) {
            return Result::failure(ApplicationError::notFound('Pipeline state not found'));
        }

        try {
            // Engine tick: markRunning -> markSucceeded -> advance to next stage
            $pipelineState->markRunning();
            $pipelineState->markSucceeded();

            // When pipeline is done (CLEANUP succeeded), mark task as succeeded
            if ($pipelineState->isDone()) {
                $task->markSucceeded();
                $this->taskRepository->save($task);
            }

            $this->pipelineStateRepository->save($pipelineState);

            return Result::success(true);
        } catch (\Exception $e) {
            return Result::failure(ApplicationError::invalidState($e->getMessage()));
        }
    }
}
