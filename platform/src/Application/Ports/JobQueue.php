<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Contracts\ErrorKind;

interface JobQueue
{
    /**
     * Enqueue a job to advance a pipeline
     * 
     * @return string Job ID
     */
    public function enqueueAdvance(TaskId $taskId): string;

    /**
     * Atomically claim the next available job
     * 
     * @return ClaimedJob|null
     */
    public function claimNext(): ?ClaimedJob;

    /**
     * Mark a job as succeeded
     */
    public function markSucceeded(string $jobId): void;

    /**
     * Mark a job as failed and schedule for retry if retryable
     */
    public function markFailed(string $jobId, ErrorKind $errorKind, bool $retryable): void;

    /**
     * Move a job to dead letter queue
     */
    public function moveToDlq(string $jobId, ErrorKind $errorKind): void;
}
