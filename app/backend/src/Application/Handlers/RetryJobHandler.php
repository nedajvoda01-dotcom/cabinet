<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Handlers;

use Cabinet\Backend\Application\Bus\Command;
use Cabinet\Backend\Application\Bus\CommandHandler;
use Cabinet\Backend\Application\Commands\Admin\RetryJobCommand;
use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Application\Shared\ApplicationError;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Domain\Pipeline\JobId;

/**
 * @implements CommandHandler<bool>
 */
final class RetryJobHandler implements CommandHandler
{
    public function __construct(
        private readonly PipelineStateRepository $pipelineStateRepository
    ) {
    }

    public function handle(Command $command): Result
    {
        if (!$command instanceof RetryJobCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $jobId = JobId::fromString($command->taskId());
        $pipelineState = $this->pipelineStateRepository->findByJobId($jobId);

        if ($pipelineState === null) {
            return Result::failure(ApplicationError::notFound('Pipeline state not found'));
        }

        try {
            // If in dead letter queue, only allow retry with explicit override flag
            if ($pipelineState->isInDeadLetter()) {
                if (!$command->allowDlqOverride()) {
                    return Result::failure(ApplicationError::permissionDenied(
                        'Cannot retry job in dead letter queue without explicit override'
                    ));
                }
                
                // Rescue from DLQ back to queued (requires manual intervention)
                $pipelineState->rescueFromDeadLetter();
            } else {
                // Normal retry: must be in failed status
                $pipelineState->scheduleRetry();
            }

            $this->pipelineStateRepository->save($pipelineState);

            return Result::success(true);
        } catch (\Exception $e) {
            return Result::failure(ApplicationError::invalidState($e->getMessage()));
        }
    }
}
