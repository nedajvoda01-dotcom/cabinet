<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

final class ClaimedJob
{
    public function __construct(
        private readonly string $jobId,
        private readonly string $taskId,
        private readonly string $kind,
        private readonly int $attempt
    ) {
    }

    public function jobId(): string
    {
        return $this->jobId;
    }

    public function taskId(): string
    {
        return $this->taskId;
    }

    public function kind(): string
    {
        return $this->kind;
    }

    public function attempt(): int
    {
        return $this->attempt;
    }
}
