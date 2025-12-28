<?php

declare(strict_types=1);

namespace Cabinet\Backend\Tests\Unit\Application;

use Cabinet\Backend\Application\Commands\Pipeline\AdvancePipelineCommand;
use Cabinet\Backend\Application\Commands\Admin\RetryJobCommand;
use Cabinet\Backend\Application\Handlers\AdvancePipelineHandler;
use Cabinet\Backend\Application\Handlers\RetryJobHandler;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineState;
use Cabinet\Backend\Domain\Tasks\Task;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryPipelineStateRepository;
use Cabinet\Backend\Infrastructure\Persistence\InMemory\InMemoryTaskRepository;
use Cabinet\Backend\Tests\TestCase;
use Cabinet\Contracts\ErrorKind;
use Cabinet\Contracts\PipelineStage;

final class PipelineHandlersTest extends TestCase
{
    public function testAdvancePipelineFromParseToPhotos(): void
    {
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();

        $taskId = TaskId::fromString('task-123');
        $task = Task::create($taskId);
        $taskRepo->save($task);

        $jobId = JobId::fromString('task-123');
        $pipelineState = PipelineState::create($jobId);
        $pipelineRepo->save($pipelineState);

        $handler = new AdvancePipelineHandler($taskRepo, $pipelineRepo);
        $command = new AdvancePipelineCommand('task-123');

        // Initially at PARSE stage
        $this->assertTrue($pipelineState->stage() === PipelineStage::PARSE, 'Should start at PARSE stage');

        // Advance
        $result = $handler->handle($command);
        $this->assertTrue($result->isSuccess(), 'Advance should succeed');

        // Should now be at PHOTOS stage
        $updatedState = $pipelineRepo->findByJobId($jobId);
        $this->assertTrue($updatedState->stage() === PipelineStage::PHOTOS, 'Should advance to PHOTOS stage');
    }

    public function testAdvancePipelineMarksTaskSucceededWhenDone(): void
    {
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();

        $taskId = TaskId::fromString('task-123');
        $task = Task::create($taskId);
        $taskRepo->save($task);

        $jobId = JobId::fromString('task-123');
        $pipelineState = PipelineState::create($jobId);
        $pipelineRepo->save($pipelineState);

        $handler = new AdvancePipelineHandler($taskRepo, $pipelineRepo);

        // Advance through all stages
        for ($i = 0; $i < 5; $i++) {
            $command = new AdvancePipelineCommand('task-123');
            $handler->handle($command);
            $pipelineState = $pipelineRepo->findByJobId($jobId);
        }

        // After all stages, task should be marked as succeeded
        $updatedTask = $taskRepo->findById($taskId);
        $this->assertTrue($updatedTask->isSucceeded(), 'Task should be marked as succeeded');
    }

    public function testRetryJobFromFailedState(): void
    {
        $pipelineRepo = new InMemoryPipelineStateRepository();

        $jobId = JobId::fromString('task-123');
        $pipelineState = PipelineState::create($jobId);
        
        // Simulate failure
        $pipelineState->markRunning();
        $pipelineState->markFailed(ErrorKind::INTERNAL_ERROR);
        $pipelineRepo->save($pipelineState);

        $handler = new RetryJobHandler($pipelineRepo);
        $command = new RetryJobCommand('task-123');
        $result = $handler->handle($command);

        $this->assertTrue($result->isSuccess(), 'Retry should succeed');

        $updatedState = $pipelineRepo->findByJobId($jobId);
        $this->assertTrue($updatedState->attemptCount() === 1, 'Attempt count should be 1 after failure');
    }

    public function testRetryJobIncrementsAttemptCount(): void
    {
        $pipelineRepo = new InMemoryPipelineStateRepository();

        $jobId = JobId::fromString('task-123');
        $pipelineState = PipelineState::create($jobId);
        
        // First attempt fails
        $pipelineState->markRunning();
        $pipelineState->markFailed(ErrorKind::INTERNAL_ERROR);
        $initialAttempts = $pipelineState->attemptCount();
        
        // Retry
        $pipelineState->scheduleRetry();
        $pipelineState->markRunning();
        $pipelineState->markFailed(ErrorKind::INTERNAL_ERROR);
        $secondAttempts = $pipelineState->attemptCount();
        
        $pipelineRepo->save($pipelineState);

        $this->assertTrue($secondAttempts === $initialAttempts + 1, 'Attempt count should increment on retry');
    }

    public function testRetryJobFromDeadLetterQueueRequiresOverride(): void
    {
        $pipelineRepo = new InMemoryPipelineStateRepository();

        $jobId = JobId::fromString('task-123');
        $pipelineState = PipelineState::create($jobId);
        
        // Move to dead letter queue
        $pipelineState->markRunning();
        $pipelineState->markFailed(ErrorKind::INTERNAL_ERROR);
        $pipelineState->moveToDeadLetter();
        $pipelineRepo->save($pipelineState);

        $handler = new RetryJobHandler($pipelineRepo);
        
        // Try retry without override
        $command = new RetryJobCommand('task-123', false);
        $result = $handler->handle($command);

        $this->assertTrue($result->isFailure(), 'Retry from DLQ should fail without override');
        $this->assertTrue($result->error()->code()->value === 'permission_denied', 'Should be permission denied');
    }

    public function testRetryJobFromDeadLetterQueueWithOverride(): void
    {
        $pipelineRepo = new InMemoryPipelineStateRepository();

        $jobId = JobId::fromString('task-123');
        $pipelineState = PipelineState::create($jobId);
        
        // Move to dead letter queue
        $pipelineState->markRunning();
        $pipelineState->markFailed(ErrorKind::INTERNAL_ERROR);
        $pipelineState->moveToDeadLetter();
        $pipelineRepo->save($pipelineState);

        $this->assertTrue($pipelineState->isInDeadLetter(), 'Should be in DLQ initially');

        $handler = new RetryJobHandler($pipelineRepo);
        
        // Retry with override
        $command = new RetryJobCommand('task-123', true, 'Manual admin override');
        $result = $handler->handle($command);

        $this->assertTrue($result->isSuccess(), 'Retry from DLQ should succeed with override');
        
        // Fetch updated state from repository
        $updatedState = $pipelineRepo->findByJobId($jobId);
        
        // After start(), the state should be back to QUEUED and not in DLQ anymore
        $this->assertTrue(!$updatedState->isInDeadLetter(), 'Should no longer be in DLQ');
    }

    public function testAdvancePipelineFailsForNonExistentTask(): void
    {
        $taskRepo = new InMemoryTaskRepository();
        $pipelineRepo = new InMemoryPipelineStateRepository();

        $handler = new AdvancePipelineHandler($taskRepo, $pipelineRepo);
        $command = new AdvancePipelineCommand('non-existent-task');
        $result = $handler->handle($command);

        $this->assertTrue($result->isFailure(), 'Should fail for non-existent task');
        $this->assertTrue($result->error()->code()->value === 'not_found', 'Error code should be not_found');
    }
}
