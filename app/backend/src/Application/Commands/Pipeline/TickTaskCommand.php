<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Commands\Pipeline;

use Cabinet\Backend\Application\Bus\Command;

final class TickTaskCommand implements Command
{
    public function __construct(
        private readonly string $taskId
    ) {
    }

    public function taskId(): string
    {
        return $this->taskId;
    }
}
