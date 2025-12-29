<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories;

use Cabinet\Backend\Application\Ports\TaskRepository;
use Cabinet\Backend\Domain\Tasks\Task;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Backend\Domain\Tasks\TaskStatus;
use PDO;

final class TasksRepository implements TaskRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(Task $task): void
    {
        $sql = <<<SQL
        INSERT INTO tasks (id, created_by, status, created_at, updated_at)
        VALUES (:id, :created_by, :status, :created_at, :updated_at)
        ON CONFLICT(id) DO UPDATE SET
            status = :status,
            updated_at = :updated_at
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $task->id()->toString(),
            ':created_by' => 'system', // Tasks don't currently track creator
            ':status' => $task->status()->value,
            ':created_at' => date('Y-m-d H:i:s'),
            ':updated_at' => date('Y-m-d H:i:s'),
        ]);
    }

    public function findById(TaskId $id): ?Task
    {
        $sql = 'SELECT * FROM tasks WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row === false) {
            return null;
        }

        return $this->hydrateTask($row);
    }

    public function findByActorAndIdempotencyKey(string $actorId, string $idempotencyKey): ?Task
    {
        $sql = <<<SQL
        SELECT t.* FROM tasks t
        INNER JOIN idempotency_keys ik ON ik.task_id = t.id
        WHERE ik.actor_id = :actor_id AND ik.idem_key = :idem_key
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':actor_id' => $actorId,
            ':idem_key' => $idempotencyKey,
        ]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row === false) {
            return null;
        }

        return $this->hydrateTask($row);
    }

    public function storeIdempotencyKey(string $actorId, string $idempotencyKey, TaskId $taskId): void
    {
        $sql = <<<SQL
        INSERT INTO idempotency_keys (actor_id, idem_key, task_id, created_at)
        VALUES (:actor_id, :idem_key, :task_id, :created_at)
        ON CONFLICT(actor_id, idem_key) DO NOTHING
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':actor_id' => $actorId,
            ':idem_key' => $idempotencyKey,
            ':task_id' => $taskId->toString(),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return Task[]
     */
    public function findAll(): array
    {
        $sql = 'SELECT * FROM tasks ORDER BY created_at DESC';
        $stmt = $this->pdo->query($sql);
        
        $tasks = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $tasks[] = $this->hydrateTask($row);
        }
        
        return $tasks;
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateTask(array $row): Task
    {
        $task = Task::create(TaskId::fromString($row['id']));
        
        // Apply status transitions to reach the current state
        $status = TaskStatus::from($row['status']);
        if ($status === TaskStatus::RUNNING) {
            $task->start();
        } elseif ($status === TaskStatus::SUCCEEDED) {
            if (!$task->isRunning()) {
                $task->start();
            }
            $task->markSucceeded();
        } elseif ($status === TaskStatus::FAILED) {
            if (!$task->isRunning()) {
                $task->start();
            }
            $task->markFailed();
        } elseif ($status === TaskStatus::CANCELLED) {
            $task->cancel();
        }

        return $task;
    }
}

