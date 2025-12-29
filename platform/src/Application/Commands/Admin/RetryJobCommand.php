<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Commands\Admin;

use Cabinet\Backend\Application\Bus\Command;

final class RetryJobCommand implements Command
{
    public function __construct(
        private readonly string $taskId,
        private readonly bool $allowDlqOverride = false,
        private readonly ?string $reason = null
    ) {
    }

    public function taskId(): string
    {
        return $this->taskId;
    }

    public function allowDlqOverride(): bool
    {
        return $this->allowDlqOverride;
    }

    public function reason(): ?string
    {
        return $this->reason;
    }
}

