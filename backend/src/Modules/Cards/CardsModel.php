<?php
declare(strict_types=1);

namespace Backend\Modules\Cards;

use PDO;
use PDOException;
use RuntimeException;

/**
 * CardsModel
 *
 * Единственная точка знания про таблицы/поля.
 *
 * Дефолтные таблицы:
 *  - cards (id, user_id, title, description, status, payload_json, is_locked, created_at, updated_at)
 *  - card_events (id, card_id, ts, from_status, to_status, action, meta_json, user_id)
 *  - audit_logs (см Admin/Auth)
 */
final class CardsModel
{
    public function __construct(private PDO $db) {}

    // -------- Cards CRUD --------

    public function createCard(array $fields): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO cards(user_id, title, description, status, payload_json, is_locked, created_at, updated_at)
            VALUES (:uid, :title, :descr, :status, :payload, :locked, NOW(), NOW())
        ");

        $stmt->execute([
            'uid' => $fields['user_id'],
            'title' => $fields['title'],
            'descr' => $fields['description'],
            'status' => $fields['status'],
            'payload' => json_encode($fields['payload'] ?? [], JSON_UNESCAPED_UNICODE),
            'locked' => $fields['is_locked'] ? 1 : 0,
        ]);

        return $this->getCardById((int)$this->db->lastInsertId());
    }

    public function getCardById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM cards WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) throw new RuntimeException("Card not found: {$id}");

        $row['payload'] = $this->decodePayload($row['payload_json'] ?? null);
        unset($row['payload_json']);

        return $row;
    }

    public function updateCard(int $id, array $patch): array
    {
        if (!$patch) return $this->getCardById($id);

        $allowed = ['title', 'description', 'payload_json', 'status', 'is_locked'];
        $set = [];
        $params = ['id' => $id];

        foreach ($patch as $k => $v) {
            if ($k === 'payload') {
                $k = 'payload_json';
                $v = json_encode($v ?? [], JSON_UNESCAPED_UNICODE);
            }
            if (!in_array($k, $allowed, true)) continue;

            $set[] = "{$k} = :{$k}";
            $params[$k] = $v;
        }

        if (!$set) return $this->getCardById($id);

        $sql = "UPDATE cards SET " . implode(', ', $set) . ", updated_at = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $this->getCardById($id);
    }

    public function deleteCard(int $id): void
    {
        $stmt = $this->db->prepare("DELETE FROM cards WHERE id = :id");
        $stmt->execute(['id' => $id]);
    }

    public function listCards(array $filters): array
    {
        $sql = "SELECT * FROM cards WHERE 1=1";
        $params = [];

        if ($filters['q']) {
            $sql .= " AND (title LIKE :q OR description LIKE :q)";
            $params['q'] = '%' . $filters['q'] . '%';
        }
        if ($filters['status']) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if ($filters['user_id']) {
            $sql .= " AND user_id = :uid";
            $params['uid'] = $filters['user_id'];
        }
        if ($filters['locked'] !== null) {
            $sql .= " AND is_locked = :locked";
            $params['locked'] = $filters['locked'] ? 1 : 0;
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
            $r['payload'] = $this->decodePayload($r['payload_json'] ?? null);
            unset($r['payload_json']);
        }
        return $rows;
    }

    // -------- Status + events --------

    public function updateCardStatus(int $id, string $newStatus): array
    {
        $stmt = $this->db->prepare("
            UPDATE cards
               SET status = :s,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['s' => $newStatus, 'id' => $id]);

        return $this->getCardById($id);
    }

    public function addCardEvent(
        int $cardId,
        ?string $fromStatus,
        string $toStatus,
        string $action,
        array $meta,
        ?int $userId
    ): void {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO card_events(card_id, ts, from_status, to_status, action, meta_json, user_id)
                VALUES (:cid, UNIX_TIMESTAMP(), :from_s, :to_s, :act, :meta, :uid)
            ");
            $stmt->execute([
                'cid' => $cardId,
                'from_s' => $fromStatus,
                'to_s' => $toStatus,
                'act' => $action,
                'meta' => json_encode($meta, JSON_UNESCAPED_UNICODE),
                'uid' => $userId,
            ]);
        } catch (PDOException) {
            // события не должны ломать поток
        }
    }

    public function writeAudit(?int $userId, string $action, string $message, string $level = 'info'): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs(ts, level, message, correlation_id, user_id, action, source)
                VALUES (UNIX_TIMESTAMP(), :lvl, :msg, NULL, :uid, :act, 'cards')
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

    private function decodePayload(?string $json): array
    {
        if (!$json) return [];
        $decoded = json_decode($json, true);
        return is_array($decoded) ? $decoded : [];
    }
}
