<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

use Cabinet\Backend\Domain\Tasks\Task;
use Cabinet\Backend\Domain\Tasks\TaskId;

interface TaskRepository
{
    public function save(Task $task): void;

    public function findById(TaskId $id): ?Task;

    public function findByActorAndIdempotencyKey(string $actorId, string $idempotencyKey): ?Task;

    /**
     * @return Task[]
     */
    public function findAll(): array;
}
