<?php
// backend/src/Queues/DlqRepository.php

namespace App\Queues;

use PDO;

final class DlqRepository
{
    public function __construct(private PDO $db) {}

    public function put(QueueJob $job): int
    {
        $stmt = $this->db->prepare(
            "INSERT INTO dlq_jobs (job_id, type, entity, entity_id, payload_json, attempts, last_error_json)
             VALUES (:job_id, :type, :entity, :entity_id, :payload_json::jsonb, :attempts, :last_error_json::jsonb)
             RETURNING id"
        );
        $stmt->execute([
            ':job_id' => $job->id,
            ':type' => $job->type,
            ':entity' => $job->entity,
            ':entity_id' => $job->entityId,
            ':payload_json' => json_encode($job->payload, JSON_UNESCAPED_UNICODE),
            ':attempts' => $job->attempts,
            ':last_error_json' => json_encode($job->lastError, JSON_UNESCAPED_UNICODE),
        ]);
        return (int)$stmt->fetchColumn();
    }

    public function list(int $limit = 100, int $offset = 0, ?string $type = null): array
    {
        $where = [];
        $params = [];
        if ($type) {
            $where[] = 'type = :type';
            $params[':type'] = $type;
        }
        $sqlWhere = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

        $stmt = $this->db->prepare(
            "SELECT * FROM dlq_jobs {$sqlWhere}
             ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function get(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM dlq_jobs WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function delete(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM dlq_jobs WHERE id=:id");
        $stmt->execute([':id' => $id]);
    }
}
