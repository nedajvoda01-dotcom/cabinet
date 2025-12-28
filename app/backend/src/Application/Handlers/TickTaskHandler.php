<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Handlers;

use Cabinet\Backend\Application\Bus\Command;
use Cabinet\Backend\Application\Bus\CommandHandler;
use Cabinet\Backend\Application\Commands\Pipeline\TickTaskCommand;
use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Application\Ports\TaskOutputRepository;
use Cabinet\Backend\Application\Ports\UnitOfWork;
use Cabinet\Backend\Application\Shared\ApplicationError;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Application\Observability\AuditLogger;
use Cabinet\Backend\Application\Observability\AuditEvent;
use Cabinet\Backend\Application\Ports\IdGenerator;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Infrastructure\Integrations\Registry\IntegrationRegistry;
use Cabinet\Contracts\PipelineStage;

/**
 * @implements CommandHandler<array<string, mixed>>
 */
final class TickTaskHandler implements CommandHandler
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly PipelineStateRepository $pipelineStateRepository,
        private readonly TaskOutputRepository $taskOutputRepository,
        private readonly IntegrationRegistry $integrationRegistry,
        private readonly UnitOfWork $unitOfWork,
        private readonly AuditLogger $auditLogger,
        private readonly IdGenerator $idGenerator
    ) {
    }

    /**
     * @return Result<array<string, mixed>>
     */
    public function handle(Command $command): Result
    {
        if (!$command instanceof TickTaskCommand) {
            throw new \InvalidArgumentException('Invalid command type');
        }

        $taskIdString = $command->taskId();
        $taskId = TaskId::fromString($taskIdString);
        $jobId = JobId::fromString($taskIdString);

        // Load task and pipeline state
        $task = $this->taskRepository->findById($taskId);
        if ($task === null) {
            return Result::failure(ApplicationError::notFound('Task not found'));
        }

        $pipelineState = $this->pipelineStateRepository->findByJobId($jobId);
        if ($pipelineState === null) {
            return Result::failure(ApplicationError::notFound('Pipeline state not found'));
        }

        // Check if pipeline is done or in dead letter
        if ($pipelineState->isDone()) {
            return Result::success([
                'status' => 'done',
                'stage' => $pipelineState->stage()->value,
            ]);
        }

        if ($pipelineState->isInDeadLetter()) {
            return Result::failure(ApplicationError::invalidState('Pipeline is in dead letter queue'));
        }

        // Get current stage
        $currentStage = $pipelineState->stage();

        // Mark as running
        $pipelineState->markRunning();

        // Execute integration for current stage
        $integrationResult = match ($currentStage) {
            PipelineStage::PARSE => $this->integrationRegistry->parser()->run($taskId),
            PipelineStage::PHOTOS => $this->integrationRegistry->photos()->run($taskId),
            PipelineStage::PUBLISH => $this->integrationRegistry->publisher()->run($taskId),
            PipelineStage::EXPORT => $this->integrationRegistry->export()->run($taskId),
            PipelineStage::CLEANUP => $this->integrationRegistry->cleanup()->run($taskId),
        };

        // Handle integration result
        if ($integrationResult->isSuccess()) {
            // Write task output
            $this->taskOutputRepository->write($taskId, $currentStage, $integrationResult->payload());

            // Mark stage as succeeded
            $pipelineState->markSucceeded();

            // If we just completed cleanup, mark task as succeeded
            if ($currentStage === PipelineStage::CLEANUP) {
                $task->start(); // ensure task is in running state
                $task->markSucceeded();
            } elseif ($task->isOpen()) {
                // Start task if it's still open
                $task->start();
            }

            // Save everything
            $this->taskRepository->save($task);
            $this->pipelineStateRepository->save($pipelineState);
            $this->unitOfWork->commit();

            // Audit: stage transition succeeded
            $auditEvent = new AuditEvent(
                id: $this->idGenerator->generate(),
                ts: (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
                action: 'pipeline.stage.succeeded',
                targetType: 'task',
                targetId: $taskIdString,
                data: [
                    'stage' => $currentStage->value,
                    'next_stage' => $pipelineState->isDone() ? null : $pipelineState->stage()->value,
                ]
            );
            $this->auditLogger->record($auditEvent);

            return Result::success([
                'status' => 'advanced',
                'completed_stage' => $currentStage->value,
                'next_stage' => $pipelineState->isDone() ? null : $pipelineState->stage()->value,
                'task_status' => $task->status()->value,
            ]);
        } else {
            // Handle failure
            if ($integrationResult->isRetryable()) {
                $pipelineState->markFailed($integrationResult->errorKind());
            } else {
                $pipelineState->moveToDeadLetter();
                $task->markFailed();
            }

            // Save
            $this->taskRepository->save($task);
            $this->pipelineStateRepository->save($pipelineState);
            $this->unitOfWork->commit();

            // Audit: stage transition failed
            $auditEvent = new AuditEvent(
                id: $this->idGenerator->generate(),
                ts: (new \DateTimeImmutable())->format('Y-m-d\TH:i:s.u\Z'),
                action: 'pipeline.stage.failed',
                targetType: 'task',
                targetId: $taskIdString,
                data: [
                    'stage' => $currentStage->value,
                    'error_kind' => $integrationResult->errorKind()?->value,
                    'retryable' => $integrationResult->isRetryable(),
                ]
            );
            $this->auditLogger->record($auditEvent);

            return Result::success([
                'status' => 'failed',
                'stage' => $currentStage->value,
                'error' => $integrationResult->errorKind()?->value,
                'retryable' => $integrationResult->isRetryable(),
            ]);
        }
    }
}
