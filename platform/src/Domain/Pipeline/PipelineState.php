<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Pipeline;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidStateTransition;
use Cabinet\Contracts\PipelineStage;
use Cabinet\Contracts\JobStatus;
use Cabinet\Contracts\ErrorKind;

final class PipelineState
{
    private PipelineStage $stage;
    private JobStatus $status;
    private int $attemptCount;
    private ?ErrorKind $lastError;

    private function __construct(
        private readonly JobId $jobId,
        PipelineStage $stage,
        JobStatus $status,
        int $attemptCount,
        ?ErrorKind $lastError
    ) {
        $this->stage = $stage;
        $this->status = $status;
        $this->attemptCount = $attemptCount;
        $this->lastError = $lastError;
    }

    public static function create(JobId $jobId): self
    {
        return new self($jobId, PipelineStage::PARSE, JobStatus::QUEUED, 0, null);
    }

    public function jobId(): JobId
    {
        return $this->jobId;
    }

    public function stage(): PipelineStage
    {
        return $this->stage;
    }

    public function status(): JobStatus
    {
        return $this->status;
    }

    public function attemptCount(): int
    {
        return $this->attemptCount;
    }

    public function lastError(): ?ErrorKind
    {
        return $this->lastError;
    }

    public function isDone(): bool
    {
        return $this->stage === PipelineStage::CLEANUP && $this->status === JobStatus::SUCCEEDED;
    }

    public function isInDeadLetter(): bool
    {
        return $this->status === JobStatus::DEAD_LETTER;
    }

    private function isTerminal(): bool
    {
        return $this->isInDeadLetter();
    }

    public function start(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                'start',
                'PipelineState'
            );
        }

        $this->status = JobStatus::QUEUED;
        $this->attemptCount = 0;
        $this->lastError = null;
    }

    public function markRunning(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                JobStatus::RUNNING->value,
                'PipelineState'
            );
        }

        $this->status = JobStatus::RUNNING;
        $this->attemptCount++;
    }

    public function markSucceeded(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                JobStatus::SUCCEEDED->value,
                'PipelineState'
            );
        }

        $this->status = JobStatus::SUCCEEDED;
        $this->lastError = null;

        // Advance to next stage if not at cleanup
        if ($this->stage !== PipelineStage::CLEANUP) {
            $this->stage = $this->getNextStage($this->stage);
            $this->status = JobStatus::QUEUED;
            $this->attemptCount = 0;
        }
    }

    public function markFailed(ErrorKind $error): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                JobStatus::FAILED->value,
                'PipelineState'
            );
        }

        $this->status = JobStatus::FAILED;
        $this->lastError = $error;
    }

    public function scheduleRetry(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                'retry',
                'PipelineState'
            );
        }

        if ($this->status !== JobStatus::FAILED) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                'retry',
                'PipelineState'
            );
        }

        $this->status = JobStatus::QUEUED;
    }

    public function moveToDeadLetter(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                JobStatus::DEAD_LETTER->value,
                'PipelineState'
            );
        }

        $this->status = JobStatus::DEAD_LETTER;
    }

    public function rescueFromDeadLetter(): void
    {
        if (!$this->isInDeadLetter()) {
            throw InvalidStateTransition::forTransition(
                "{$this->stage->value}:{$this->status->value}",
                'rescue from DLQ',
                'PipelineState'
            );
        }

        $this->status = JobStatus::QUEUED;
        $this->attemptCount = 0;
        $this->lastError = null;
    }

    private function getNextStage(PipelineStage $current): PipelineStage
    {
        return match ($current) {
            PipelineStage::PARSE => PipelineStage::PHOTOS,
            PipelineStage::PHOTOS => PipelineStage::PUBLISH,
            PipelineStage::PUBLISH => PipelineStage::EXPORT,
            PipelineStage::EXPORT => PipelineStage::CLEANUP,
            PipelineStage::CLEANUP => throw new \LogicException('CLEANUP is the final stage'),
        };
    }
}
