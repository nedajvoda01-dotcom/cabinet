<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\InMemory;

use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Domain\Tasks\Task;
use Cabinet\Backend\Domain\Tasks\TaskId;

final class InMemoryTaskRepository implements TaskRepository
{
    /** @var array<string, Task> */
    private array $tasks = [];

    /** @var array<string, TaskId> */
    private array $idempotencyMap = [];

    public function save(Task $task): void
    {
        $this->tasks[$task->id()->toString()] = $task;
    }

    public function findById(TaskId $id): ?Task
    {
        return $this->tasks[$id->toString()] ?? null;
    }

    public function findByActorAndIdempotencyKey(string $actorId, string $idempotencyKey): ?Task
    {
        $key = sprintf('%s:%s', $actorId, $idempotencyKey);
        $taskId = $this->idempotencyMap[$key] ?? null;

        if ($taskId === null) {
            return null;
        }

        return $this->findById($taskId);
    }

    public function storeIdempotencyKey(string $actorId, string $idempotencyKey, TaskId $taskId): void
    {
        $key = sprintf('%s:%s', $actorId, $idempotencyKey);
        $this->idempotencyMap[$key] = $taskId;
    }

    /**
     * @return Task[]
     */
    public function findAll(): array
    {
        return array_values($this->tasks);
    }
}
