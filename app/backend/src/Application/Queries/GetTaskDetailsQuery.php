<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Queries;

use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineStage;
use Cabinet\Contracts\ErrorKind;

final class GetTaskDetailsQuery
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly PipelineStateRepository $pipelineStateRepository
    ) {
    }

    public function execute(string $taskId): Result
    {
        try {
            $id = TaskId::fromString($taskId);
        } catch (\Throwable $e) {
            return Result::failure(ErrorKind::VALIDATION_ERROR, 'Invalid task ID');
        }
        
        $task = $this->taskRepository->findById($id);
        
        if ($task === null) {
            return Result::failure(ErrorKind::NOT_FOUND, 'Task not found');
        }
        
        // Get pipeline state
        $pipelineState = $this->pipelineStateRepository->findByJobId(JobId::fromString($taskId));
        
        $stages = [];
        if ($pipelineState !== null) {
            foreach (PipelineStage::cases() as $stage) {
                $stageState = $pipelineState->getStageState($stage);
                $stages[] = [
                    'stage' => $stage->value,
                    'status' => $stageState->status()->value,
                    'attempt' => $stageState->attemptCount(),
                    'error' => $stageState->errorKind()?->value,
                ];
            }
        }
        
        $result = [
            'id' => $task->id()->toString(),
            'status' => $task->status()->value,
            'currentStage' => $pipelineState?->currentStage()->value,
            'stages' => $stages,
        ];
        
        return Result::success($result);
    }
}
