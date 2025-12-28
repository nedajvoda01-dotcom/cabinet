<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Queries;

use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Application\Shared\Result;
use Cabinet\Contracts\ErrorKind;

final class ListTasksQuery
{
    public function __construct(
        private readonly TaskRepository $taskRepository,
        private readonly PipelineStateRepository $pipelineStateRepository
    ) {
    }

    public function execute(): Result
    {
        $tasks = $this->taskRepository->findAll();
        
        $result = [];
        foreach ($tasks as $task) {
            $taskId = $task->id()->toString();
            
            // Get pipeline state
            $pipelineState = $this->pipelineStateRepository->findByJobId(
                \Cabinet\Backend\Domain\Pipeline\JobId::fromString($taskId)
            );
            
            $currentStage = null;
            $attempts = 0;
            
            if ($pipelineState !== null) {
                $currentStage = $pipelineState->currentStage()->value;
                $stageState = $pipelineState->getStageState($pipelineState->currentStage());
                $attempts = $stageState->attemptCount();
            }
            
            $result[] = [
                'id' => $taskId,
                'status' => $task->status()->value,
                'currentStage' => $currentStage,
                'attempts' => $attempts,
            ];
        }
        
        return Result::success(['tasks' => $result]);
    }
}
