<?php
declare(strict_types=1);

namespace Backend\Modules\Publish;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PublishModel
 *
 * Единственная точка знания про таблицы/поля/площадки.
 *
 * Дефолтные таблицы:
 *  - publish_tasks (
 *      id, card_id, platform, account_id,
 *      status, attempts, params_json,
 *      external_id, external_url,
 *      error_code, error_message,
 *      created_at, updated_at, finished_at
 *    )
 *  - cards (payload_json)
 *  - audit_logs
 */
final class PublishModel
{
    public function __construct(private PDO $db) {}

    // -------- Tasks --------

    public function createTask(int $cardId, string $platform, ?int $accountId, array $params): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO publish_tasks(card_id, platform, account_id, status, attempts, params_json, created_at, updated_at)
            VALUES (:cid, :pf, :aid, 'queued', 0, :params, NOW(), NOW())
        ");
        $stmt->execute([
            'cid' => $cardId,
            'pf' => $platform,
            'aid' => $accountId,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->getTaskById((int)$this->db->lastInsertId());
    }

    public function getTaskById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM publish_tasks WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) throw new RuntimeException("Publish task not found: {$id}");

        $row['params'] = $this->decodeJson($row['params_json'] ?? null);
        unset($row['params_json']);

        return $row;
    }

    public function listTasks(array $filters): array
    {
        $sql = "SELECT * FROM publish_tasks WHERE 1=1";
        $params = [];

        if ($filters['status']) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if ($filters['platform']) {
            $sql .= " AND platform = :pf";
            $params['pf'] = $filters['platform'];
        }
        if ($filters['account_id']) {
            $sql .= " AND account_id = :aid";
            $params['aid'] = $filters['account_id'];
        }
        if ($filters['card_id']) {
            $sql .= " AND card_id = :cid";
            $params['cid'] = $filters['card_id'];
        }
        if ($filters['from_ts']) {
            $sql .= " AND UNIX_TIMESTAMP(created_at) >= :from_ts";
            $params['from_ts'] = $filters['from_ts'];
        }
        if ($filters['to_ts']) {
            $sql .= " AND UNIX_TIMESTAMP(created_at) <= :to_ts";
            $params['to_ts'] = $filters['to_ts'];
        }

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue('limit', (int)$filters['limit'], PDO::PARAM_INT);
        $stmt->bindValue('offset', (int)$filters['offset'], PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$r) {
            $r['params'] = $this->decodeJson($r['params_json'] ?? null);
            unset($r['params_json']);
        }
        return $rows;
    }

    public function updateTaskStatus(
        int $taskId,
        string $status,
        ?string $externalId = null,
        ?string $externalUrl = null,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): array {
        $stmt = $this->db->prepare("
            UPDATE publish_tasks
               SET status = :s,
                   external_id = :eid,
                   external_url = :eurl,
                   error_code = :ec,
                   error_message = :em,
                   finished_at = CASE WHEN :s IN ('done','failed','blocked','canceled') THEN NOW() ELSE finished_at END,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            's' => $status,
            'eid' => $externalId,
            'eurl' => $externalUrl,
            'ec' => $errorCode,
            'em' => $errorMessage,
            'id' => $taskId,
        ]);

        return $this->getTaskById($taskId);
    }

    public function incrementAttempts(int $taskId): void
    {
        $stmt = $this->db->prepare("
            UPDATE publish_tasks
               SET attempts = attempts + 1, updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['id' => $taskId]);
    }

    // -------- Card payload bridge --------

    public function attachPublishResultToCard(int $cardId, string $platform, array $result): void
    {
        $stmt = $this->db->prepare("SELECT payload_json FROM cards WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $cardId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        $payload = $this->decodeJson($row['payload_json'] ?? null);
        $payload['publish'] ??= [];
        $payload['publish'][$platform] = array_merge(
            $payload['publish'][$platform] ?? [],
            $result
        );

        $upd = $this->db->prepare("
            UPDATE cards
               SET payload_json = :p, updated_at = NOW()
             WHERE id=:id
        ");
        $upd->execute([
            'p' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'id' => $cardId,
        ]);
    }

    // -------- Metrics --------

    /**
     * Очень простая агрегация по publish_tasks.
     * Возвращает buckets => counts per status.
     */
    public function getMetrics(int $fromTs, int $toTs, ?string $platform, ?int $accountId, int $bucketSec): array
    {
        $bucketSec = max(60, $bucketSec);
        $sql = "
            SELECT
              FLOOR(UNIX_TIMESTAMP(created_at)/:b)*:b AS bucket_ts,
              status,
              COUNT(*) as cnt
            FROM publish_tasks
            WHERE UNIX_TIMESTAMP(created_at) BETWEEN :from_ts AND :to_ts
        ";
        $params = [
            'b' => $bucketSec,
            'from_ts' => $fromTs,
            'to_ts' => $toTs,
        ];

        if ($platform) {
            $sql .= " AND platform = :pf";
            $params['pf'] = $platform;
        }
        if ($accountId) {
            $sql .= " AND account_id = :aid";
            $params['aid'] = $accountId;
        }

        $sql .= " GROUP BY bucket_ts, status ORDER BY bucket_ts ASC";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v, is_int($v) ? PDO::PARAM_INT : PDO::PARAM_STR);
        }
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $out = [];
        foreach ($rows as $r) {
            $bt = (int)$r['bucket_ts'];
            $st = (string)$r['status'];
            $out[$bt] ??= ['bucket_ts' => $bt, 'counts' => []];
            $out[$bt]['counts'][$st] = (int)$r['cnt'];
        }

        return array_values($out);
    }

    // -------- Audit --------

    public function writeAudit(?int $userId, string $action, string $message, string $level='info'): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs(ts, level, message, correlation_id, user_id, action, source)
                VALUES (UNIX_TIMESTAMP(), :lvl, :msg, NULL, :uid, :act, 'publish')
            ");
            $stmt->execute([
                'lvl' => $level,
                'msg' => $message,
                'uid' => $userId,
                'act' => $action,
            ]);
        } catch (PDOException) {}
    }

    // -------- Utils --------
    private function decodeJson(?string $json): array
    {
        if (!$json) return [];
        $d = json_decode($json, true);
        return is_array($d) ? $d : [];
    }
}
