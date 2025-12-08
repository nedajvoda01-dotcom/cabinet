<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

use PDO;
use PDOException;
use RuntimeException;

/**
 * AdminModel
 *
 * Единственное место, где мы "знаем" про таблицы/хранилища/очереди.
 * Если у вас другие названия — правим только тут.
 *
 * Дефолтные таблицы (можно заменить):
 *  - jobs          (общая таблица задач pipeline)
 *  - queues        (конфиг / состояние очередей)
 *  - audit_logs    (аудит)
 *  - system_logs   (системные логи)
 *  - users
 *  - users_roles   (junction)
 */
final class AdminModel
{
    public function __construct(private PDO $db) {}

    // ---------------- Queues ----------------

    public function getQueuesOverview(): array
    {
        // TODO: если у вас есть отдельное хранилище статистики очередей — подключить здесь.
        // Пока читаем таблицу queues, если её нет — отдаём пусто.
        try {
            $stmt = $this->db->query("
                SELECT
                    type,
                    paused,
                    depth,
                    in_flight,
                    retrying,
                    updated_at
                FROM queues
                ORDER BY type ASC
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (PDOException) {
            return [];
        }
    }

    public function getQueueJobs(string $type, int $limit, int $offset, ?string $status = null): array
    {
        // jobs.status предполагаем: queued|in_flight|retrying|dlq|done|failed
        $sql = "
            SELECT
                id,
                type,
                status,
                attempts,
                next_retry_at,
                last_error_code,
                last_error_message,
                entity_ref,
                created_at,
                updated_at
            FROM jobs
            WHERE type = :type
        ";

        $params = ['type' => $type];

        if ($status) {
            $sql .= " AND status = :status ";
            $params['status'] = $status;
        } else {
            $sql .= " AND status IN ('queued','in_flight','retrying') ";
        }

        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset ";

        $stmt = $this->db->prepare($sql);
        $stmt->bindValue('type', $type);
        if (isset($params['status'])) {
            $stmt->bindValue('status', $params['status']);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function setQueuePaused(string $type, bool $paused): void
    {
        // Считаем, что queues.type уникален.
        $stmt = $this->db->prepare("
            UPDATE queues
               SET paused = :paused,
                   updated_at = NOW()
             WHERE type = :type
        ");
        $stmt->execute([
            'paused' => $paused ? 1 : 0,
            'type' => $type,
        ]);
    }

    // ---------------- DLQ ----------------

    public function getDlqItems(int $limit, int $offset, ?string $type = null): array
    {
        $sql = "
            SELECT
                id,
                type,
                status,
                attempts,
                next_retry_at,
                last_error_code,
                last_error_message,
                entity_ref,
                created_at,
                updated_at
            FROM jobs
            WHERE status = 'dlq'
        ";
        $params = [];
        if ($type) {
            $sql .= " AND type = :type ";
            $params['type'] = $type;
        }
        $sql .= " ORDER BY id DESC LIMIT :limit OFFSET :offset ";

        $stmt = $this->db->prepare($sql);
        if ($type) $stmt->bindValue('type', $type);
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function getDlqItem(int $id): array
    {
        $stmt = $this->db->prepare("
            SELECT *
              FROM jobs
             WHERE id = :id AND status = 'dlq'
        ");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) throw new RuntimeException("DLQ item not found: {$id}");
        return $row;
    }

    public function markDlqRetryRequested(int $id): void
    {
        // Переводим в retrying и сбрасываем next_retry_at на NOW().
        $stmt = $this->db->prepare("
            UPDATE jobs
               SET status = 'retrying',
                   attempts = attempts + 1,
                   next_retry_at = NOW(),
                   updated_at = NOW()
             WHERE id = :id AND status = 'dlq'
        ");
        $stmt->execute(['id' => $id]);
    }

    public function bulkRetryDlq(?string $type, ?int $limit = null): int
    {
        // Выбираем id'шники и переводим в retrying.
        $sql = "SELECT id FROM jobs WHERE status = 'dlq'";
        $params = [];
        if ($type) {
            $sql .= " AND type = :type";
            $params['type'] = $type;
        }
        $sql .= " ORDER BY id DESC";
        if ($limit && $limit > 0) {
            $sql .= " LIMIT :limit";
        }

        $stmt = $this->db->prepare($sql);
        if ($type) $stmt->bindValue('type', $type);
        if ($limit && $limit > 0) $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->execute();

        $ids = array_map(fn($r) => (int)$r['id'], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        if (!$ids) return 0;

        $in = implode(',', array_fill(0, count($ids), '?'));
        $upd = $this->db->prepare("
            UPDATE jobs
               SET status = 'retrying',
                   attempts = attempts + 1,
                   next_retry_at = NOW(),
                   updated_at = NOW()
             WHERE id IN ($in)
        ");
        $upd->execute($ids);

        return count($ids);
    }

    // ---------------- Health ----------------

    public function getSystemHealth(): array
    {
        // Минимальная health-сводка.
        // При желании добавим проверки интеграций через Adapters.
        $dbOk = true;
        $dbLatencyMs = null;

        try {
            $t0 = microtime(true);
            $this->db->query("SELECT 1");
            $dbLatencyMs = (int)round((microtime(true) - $t0) * 1000);
        } catch (PDOException) {
            $dbOk = false;
        }

        return [
            'db' => [
                'ok' => $dbOk,
                'latency_ms' => $dbLatencyMs,
            ],
            'time' => time(),
        ];
    }

    // ---------------- Logs ----------------

    public function getLogs(
        int $limit,
        int $offset,
        ?int $userId,
        ?int $cardId,
        ?string $action,
        ?int $fromTs,
        ?int $toTs
    ): array {
        // Склеиваем audit_logs и system_logs в один стрим.
        // Оба лога должны иметь: id, ts, level, message, correlation_id, user_id, card_id, action.
        $sql = "
            SELECT * FROM (
                SELECT
                    id,
                    ts,
                    level,
                    message,
                    correlation_id,
                    user_id,
                    card_id,
                    action,
                    'audit' AS source
                FROM audit_logs
                UNION ALL
                SELECT
                    id,
                    ts,
                    level,
                    message,
                    correlation_id,
                    user_id,
                    card_id,
                    action,
                    'system' AS source
                FROM system_logs
            ) AS logs
            WHERE 1=1
        ";

        $params = [];
        if ($userId) { $sql .= " AND user_id = :user_id"; $params['user_id'] = $userId; }
        if ($cardId) { $sql .= " AND card_id = :card_id"; $params['card_id'] = $cardId; }
        if ($action) { $sql .= " AND action = :action"; $params['action'] = $action; }
        if ($fromTs) { $sql .= " AND ts >= :from_ts"; $params['from_ts'] = $fromTs; }
        if ($toTs) { $sql .= " AND ts <= :to_ts"; $params['to_ts'] = $toTs; }

        $sql .= " ORDER BY ts DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    // ---------------- Users / Roles ----------------

    public function getUsers(int $limit, int $offset, ?string $q, ?string $role): array
    {
        $sql = "
            SELECT u.*
              FROM users u
        ";
        if ($role) {
            $sql .= " INNER JOIN users_roles ur ON ur.user_id = u.id AND ur.role = :role ";
        }
        $sql .= " WHERE 1=1 ";

        $params = [];
        if ($q) {
            $sql .= " AND (u.email LIKE :q OR u.name LIKE :q) ";
            $params['q'] = "%{$q}%";
        }

        $sql .= " ORDER BY u.id DESC LIMIT :limit OFFSET :offset";
        $stmt = $this->db->prepare($sql);

        if ($role) $stmt->bindValue('role', $role);
        foreach ($params as $k => $v) {
            $stmt->bindValue($k, $v);
        }
        $stmt->bindValue('limit', $limit, PDO::PARAM_INT);
        $stmt->bindValue('offset', $offset, PDO::PARAM_INT);

        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
    }

    public function updateUserRoles(int $userId, array $roles): array
    {
        // Полная замена ролей пользователя.
        $this->db->beginTransaction();
        try {
            $del = $this->db->prepare("DELETE FROM users_roles WHERE user_id = :id");
            $del->execute(['id' => $userId]);

            $ins = $this->db->prepare("
                INSERT INTO users_roles(user_id, role)
                VALUES (:id, :role)
            ");
            foreach ($roles as $r) {
                $ins->execute(['id' => $userId, 'role' => $r]);
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new RuntimeException("Failed to update roles: {$e->getMessage()}");
        }

        // Вернём текущие роли.
        $stmt = $this->db->prepare("
            SELECT role FROM users_roles WHERE user_id = :id
        ");
        $stmt->execute(['id' => $userId]);
        return array_map(fn($r) => $r['role'], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
    }
}
