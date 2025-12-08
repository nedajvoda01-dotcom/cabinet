<?php
// backend/src/Queues/QueueRepository.php

namespace App\Queues;

use PDO;
use Throwable;

final class QueueRepository
{
    public function __construct(private PDO $db) {}

    public function enqueue(
        string $type,
        string $entity,
        int $entityId,
        array $payload = []
    ): QueueJob {
        QueueTypes::assertValid($type);

        $stmt = $this->db->prepare(
            "INSERT INTO queue_jobs (type, entity, entity_id, payload_json, status)
             VALUES (:type, :entity, :entity_id, :payload_json::jsonb, 'queued')
             RETURNING *"
        );
        $stmt->execute([
            ':type' => $type,
            ':entity' => $entity,
            ':entity_id' => $entityId,
            ':payload_json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        return QueueJob::fromRow($stmt->fetch(PDO::FETCH_ASSOC));
    }

    /**
     * Взять следующую доступную задачу.
     * - status in (queued, retrying)
     * - next_retry_at is null or <= now
     * Лочим транзакционно, чтобы два воркера не взяли одно и то же.
     */
    public function fetchNext(string $type, string $workerId): ?QueueJob
    {
        QueueTypes::assertValid($type);

        $this->db->beginTransaction();
        try {
            $stmt = $this->db->prepare(
                "SELECT * FROM queue_jobs
                 WHERE type=:type
                   AND status IN ('queued','retrying')
                   AND (next_retry_at IS NULL OR next_retry_at <= NOW())
                   AND (locked_at IS NULL OR locked_at <= NOW() - INTERVAL '10 minutes')
                 ORDER BY id ASC
                 FOR UPDATE SKIP LOCKED
                 LIMIT 1"
            );
            $stmt->execute([':type' => $type]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$row) {
                $this->db->commit();
                return null;
            }

            $upd = $this->db->prepare(
                "UPDATE queue_jobs
                 SET status='processing', locked_at=NOW(), locked_by=:workerId, updated_at=NOW()
                 WHERE id=:id
                 RETURNING *"
            );
            $upd->execute([':workerId' => $workerId, ':id' => $row['id']]);

            $this->db->commit();
            return QueueJob::fromRow($upd->fetch(PDO::FETCH_ASSOC));
        } catch (Throwable $e) {
            $this->db->rollBack();
            throw $e;
        }
    }

    public function markDone(int $id): void
    {
        $stmt = $this->db->prepare(
            "UPDATE queue_jobs SET status='done', locked_at=NULL, locked_by=NULL, updated_at=NOW()
             WHERE id=:id"
        );
        $stmt->execute([':id' => $id]);
    }

    public function markRetrying(int $id, int $attempts, string $nextRetryAt, array $lastError): void
    {
        $stmt = $this->db->prepare(
            "UPDATE queue_jobs
             SET status='retrying',
                 attempts=:attempts,
                 next_retry_at=:next_retry_at,
                 last_error_json=:last_error_json::jsonb,
                 locked_at=NULL,
                 locked_by=NULL,
                 updated_at=NOW()
             WHERE id=:id"
        );
        $stmt->execute([
            ':id' => $id,
            ':attempts' => $attempts,
            ':next_retry_at' => $nextRetryAt,
            ':last_error_json' => json_encode($lastError, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function markDead(int $id, int $attempts, array $lastError): void
    {
        $stmt = $this->db->prepare(
            "UPDATE queue_jobs
             SET status='dead',
                 attempts=:attempts,
                 last_error_json=:last_error_json::jsonb,
                 locked_at=NULL,
                 locked_by=NULL,
                 updated_at=NOW()
             WHERE id=:id"
        );
        $stmt->execute([
            ':id' => $id,
            ':attempts' => $attempts,
            ':last_error_json' => json_encode($lastError, JSON_UNESCAPED_UNICODE),
        ]);
    }

    public function get(int $id): ?QueueJob
    {
        $stmt = $this->db->prepare("SELECT * FROM queue_jobs WHERE id=:id");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ? QueueJob::fromRow($row) : null;
    }

    // ---- admin helpers ----

    public function depthByType(): array
    {
        $stmt = $this->db->query(
            "SELECT type,
                    COUNT(*) FILTER (WHERE status IN ('queued','retrying')) AS depth,
                    COUNT(*) FILTER (WHERE status='processing') AS in_flight,
                    COUNT(*) FILTER (WHERE status='retrying') AS retrying
             FROM queue_jobs
             GROUP BY type"
        );
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function listJobs(string $type, int $limit = 50, int $offset = 0): array
    {
        QueueTypes::assertValid($type);
        $stmt = $this->db->prepare(
            "SELECT * FROM queue_jobs
             WHERE type=:type
             ORDER BY id DESC LIMIT :limit OFFSET :offset"
        );
        $stmt->bindValue(':type', $type);
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
