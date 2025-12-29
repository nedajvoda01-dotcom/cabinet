<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Tasks;

use Cabinet\Backend\Domain\Shared\Exceptions\InvalidStateTransition;

enum TaskStatus: string
{
    case OPEN = 'open';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case CANCELLED = 'cancelled';
}

final class Task
{
    private TaskStatus $status;

    private function __construct(
        private readonly TaskId $id,
        TaskStatus $status
    ) {
        $this->status = $status;
    }

    public static function create(TaskId $id): self
    {
        return new self($id, TaskStatus::OPEN);
    }

    public function id(): TaskId
    {
        return $this->id;
    }

    public function status(): TaskStatus
    {
        return $this->status;
    }

    public function isOpen(): bool
    {
        return $this->status === TaskStatus::OPEN;
    }

    public function isRunning(): bool
    {
        return $this->status === TaskStatus::RUNNING;
    }

    public function isSucceeded(): bool
    {
        return $this->status === TaskStatus::SUCCEEDED;
    }

    public function isFailed(): bool
    {
        return $this->status === TaskStatus::FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === TaskStatus::CANCELLED;
    }

    private function isTerminal(): bool
    {
        return $this->isSucceeded() || $this->isFailed() || $this->isCancelled();
    }

    public function start(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                $this->status->value,
                TaskStatus::RUNNING->value,
                'Task'
            );
        }

        $this->status = TaskStatus::RUNNING;
    }

    public function markSucceeded(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                $this->status->value,
                TaskStatus::SUCCEEDED->value,
                'Task'
            );
        }

        $this->status = TaskStatus::SUCCEEDED;
    }

    public function markFailed(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                $this->status->value,
                TaskStatus::FAILED->value,
                'Task'
            );
        }

        $this->status = TaskStatus::FAILED;
    }

    public function cancel(): void
    {
        if ($this->isTerminal()) {
            throw InvalidStateTransition::forTransition(
                $this->status->value,
                TaskStatus::CANCELLED->value,
                'Task'
            );
        }

        $this->status = TaskStatus::CANCELLED;
    }
}
