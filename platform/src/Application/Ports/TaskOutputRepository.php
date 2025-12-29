<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Contracts\PipelineStage;

interface TaskOutputRepository
{
    /**
     * @param array<string, mixed> $payload
     */
    public function write(TaskId $taskId, PipelineStage $stage, array $payload): void;

    /**
     * Returns a map of stage->payload or list of records
     * @return array<string, mixed>
     */
    public function read(TaskId $taskId): array;
}
