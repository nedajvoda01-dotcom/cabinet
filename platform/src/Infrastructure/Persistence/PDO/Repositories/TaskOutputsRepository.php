<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories;

use Cabinet\Backend\Application\Ports\TaskOutputRepository;
use Cabinet\Backend\Domain\Tasks\TaskId;
use Cabinet\Contracts\PipelineStage;
use PDO;

final class TaskOutputsRepository implements TaskOutputRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    /**
     * @param array<string, mixed> $payload
     */
    public function write(TaskId $taskId, PipelineStage $stage, array $payload): void
    {
        $sql = <<<SQL
        INSERT INTO task_outputs (task_id, stage, payload_json, created_at)
        VALUES (:task_id, :stage, :payload_json, :created_at)
        ON CONFLICT(task_id, stage) DO UPDATE SET
            payload_json = :payload_json,
            created_at = :created_at
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':task_id' => $taskId->toString(),
            ':stage' => $stage->value,
            ':payload_json' => json_encode($payload),
            ':created_at' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    public function read(TaskId $taskId): array
    {
        $sql = <<<SQL
        SELECT stage, payload_json, created_at
        FROM task_outputs
        WHERE task_id = :task_id
        ORDER BY 
            CASE stage
                WHEN 'parse' THEN 1
                WHEN 'photos' THEN 2
                WHEN 'publish' THEN 3
                WHEN 'export' THEN 4
                WHEN 'cleanup' THEN 5
            END
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':task_id' => $taskId->toString()]);

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $result = [];
        foreach ($rows as $row) {
            $result[$row['stage']] = [
                'payload' => json_decode($row['payload_json'], true),
                'created_at' => $row['created_at'],
            ];
        }

        return $result;
    }
}
