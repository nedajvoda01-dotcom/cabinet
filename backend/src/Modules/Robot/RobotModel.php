<?php
declare(strict_types=1);

namespace Modules\Robot;

use PDO;

final class RobotModel
{
    public function __construct(
        private PDO $db
    ) {}

    public function createRun(array $data): int
    {
        RobotSchemas::assertStatus($data['status']);

        $stmt = $this->db->prepare("
            INSERT INTO robot_runs
                (card_id, publish_job_id, idempotency_key, status, attempt, payload_json, external_ref_json, last_error_json, created_at, updated_at)
            VALUES
                (:card_id, :publish_job_id, :idempotency_key, :status, :attempt, :payload_json, :external_ref_json, :last_error_json, NOW(), NOW())
        ");
        $stmt->execute([
            ':card_id' => $data['card_id'],
            ':publish_job_id' => $data['publish_job_id'],
            ':idempotency_key' => $data['idempotency_key'],
            ':status' => $data['status'],
            ':attempt' => $data['attempt'] ?? 1,
            ':payload_json' => json_encode($data['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            ':external_ref_json' => json_encode($data['external_ref'] ?? [], JSON_UNESCAPED_UNICODE),
            ':last_error_json' => json_encode($data['last_error'] ?? null, JSON_UNESCAPED_UNICODE),
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM robot_runs WHERE id = :id LIMIT 1");
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findLatestByIdempotencyKey(string $key): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM robot_runs
            WHERE idempotency_key = :key
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':key' => $key]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateRun(int $id, array $patch): void
    {
        if (isset($patch['status'])) {
            RobotSchemas::assertStatus($patch['status']);
        }

        $fields = [];
        $params = [':id' => $id];

        foreach ($patch as $k => $v) {
            switch ($k) {
                case 'status':
                case 'attempt':
                case 'idempotency_key':
                    $fields[] = "{$k} = :{$k}";
                    $params[":{$k}"] = $v;
                    break;
                case 'payload':
                    $fields[] = "payload_json = :payload_json";
                    $params[':payload_json'] = json_encode($v, JSON_UNESCAPED_UNICODE);
                    break;
                case 'external_ref':
                    $fields[] = "external_ref_json = :external_ref_json";
                    $params[':external_ref_json'] = json_encode($v, JSON_UNESCAPED_UNICODE);
                    break;
                case 'last_error':
                    $fields[] = "last_error_json = :last_error_json";
                    $params[':last_error_json'] = json_encode($v, JSON_UNESCAPED_UNICODE);
                    break;
            }
        }

        if (!$fields) return;

        $sql = "UPDATE robot_runs SET " . implode(', ', $fields) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
    }

    /**
     * Runs that are waiting for external status updates.
     */
    public function getRunsForSync(int $limit = 100): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM robot_runs
            WHERE status IN ('processing','external_wait')
            ORDER BY updated_at ASC
            LIMIT :limit
        ");
        $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }
}
