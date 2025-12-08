<?php
declare(strict_types=1);

namespace Backend\Modules\Parser;

use PDO;
use PDOException;
use RuntimeException;

/**
 * ParserModel
 *
 * Единственная точка знания про таблицы/поля и внешние парсер-интеграции.
 *
 * Дефолтные таблицы:
 *  - parser_tasks (
 *      id, card_id, type, status, attempts,
 *      source_url, params_json,
 *      parsed_payload_json,
 *      error_code, error_message,
 *      created_at, updated_at, finished_at
 *    )
 *  - cards (для апдейта payload)
 *  - audit_logs
 */
final class ParserModel
{
    public function __construct(private PDO $db) {}

    // -------- Tasks --------

    public function createTask(int $cardId, string $type, ?string $sourceUrl, array $params): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO parser_tasks(card_id, type, status, attempts, source_url, params_json, created_at, updated_at)
            VALUES (:cid, :type, 'queued', 0, :url, :params, NOW(), NOW())
        ");
        $stmt->execute([
            'cid' => $cardId,
            'type' => $type,
            'url' => $sourceUrl,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->getTaskById((int)$this->db->lastInsertId());
    }

    public function getTaskById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM parser_tasks WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) throw new RuntimeException("Parser task not found: {$id}");

        $row['params'] = $this->decodeJson($row['params_json'] ?? null);
        $row['parsed_payload'] = $this->decodeJson($row['parsed_payload_json'] ?? null);
        unset($row['params_json'], $row['parsed_payload_json']);

        return $row;
    }

    public function listTasks(array $filters): array
    {
        $sql = "SELECT * FROM parser_tasks WHERE 1=1";
        $params = [];

        if ($filters['status']) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if ($filters['type']) {
            $sql .= " AND type = :type";
            $params['type'] = $filters['type'];
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
            $r['parsed_payload'] = $this->decodeJson($r['parsed_payload_json'] ?? null);
            unset($r['params_json'], $r['parsed_payload_json']);
        }
        return $rows;
    }

    public function updateTaskStatus(
        int $taskId,
        string $status,
        ?array $parsedPayload = null,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): array {
        $stmt = $this->db->prepare("
            UPDATE parser_tasks
               SET status = :s,
                   parsed_payload_json = :pp,
                   error_code = :ec,
                   error_message = :em,
                   attempts = CASE WHEN :s IN ('queued','running') THEN attempts ELSE attempts END,
                   finished_at = CASE WHEN :s IN ('done','failed') THEN NOW() ELSE finished_at END,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            's' => $status,
            'pp' => $parsedPayload !== null ? json_encode($parsedPayload, JSON_UNESCAPED_UNICODE) : null,
            'ec' => $errorCode,
            'em' => $errorMessage,
            'id' => $taskId,
        ]);

        return $this->getTaskById($taskId);
    }

    public function incrementAttempts(int $taskId): void
    {
        $stmt = $this->db->prepare("
            UPDATE parser_tasks
               SET attempts = attempts + 1,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['id' => $taskId]);
    }

    // -------- Cards payload update --------

    public function mergeCardPayload(int $cardId, array $parsedPayload): void
    {
        // payload_json — единый JSON в cards.
        // Мержим поверх существующих ключей.
        $cardStmt = $this->db->prepare("SELECT payload_json FROM cards WHERE id = :id LIMIT 1");
        $cardStmt->execute(['id' => $cardId]);
        $row = $cardStmt->fetch(PDO::FETCH_ASSOC);

        $current = $this->decodeJson($row['payload_json'] ?? null);
        $merged = array_replace_recursive($current, $parsedPayload);

        $upd = $this->db->prepare("
            UPDATE cards
               SET payload_json = :p,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $upd->execute([
            'p' => json_encode($merged, JSON_UNESCAPED_UNICODE),
            'id' => $cardId,
        ]);
    }

    // -------- Audit --------

    public function writeAudit(?int $userId, string $action, string $message, string $level = 'info'): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs(ts, level, message, correlation_id, user_id, action, source)
                VALUES (UNIX_TIMESTAMP(), :lvl, :msg, NULL, :uid, :act, 'parser')
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
