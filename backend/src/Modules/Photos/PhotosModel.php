<?php
declare(strict_types=1);

namespace Backend\Modules\Photos;

use PDO;
use PDOException;
use RuntimeException;

/**
 * PhotosModel
 *
 * Единственная точка знания о таблицах/полях и фото-хранилище.
 *
 * Дефолтные таблицы:
 *  - photo_tasks (
 *      id, card_id, mode, status, attempts,
 *      source_urls_json, params_json,
 *      error_code, error_message,
 *      created_at, updated_at, finished_at
 *    )
 *  - photos (
 *      id, card_id, url, width, height, kind, meta_json,
 *      is_primary, created_at
 *    )
 *  - cards (payload_json)
 *  - audit_logs
 */
final class PhotosModel
{
    public function __construct(private PDO $db) {}

    // -------- Tasks --------

    public function createTask(int $cardId, string $mode, array $sourceUrls, array $params): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO photo_tasks(card_id, mode, status, attempts, source_urls_json, params_json, created_at, updated_at)
            VALUES (:cid, :mode, 'queued', 0, :urls, :params, NOW(), NOW())
        ");
        $stmt->execute([
            'cid' => $cardId,
            'mode' => $mode,
            'urls' => json_encode($sourceUrls, JSON_UNESCAPED_UNICODE),
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->getTaskById((int)$this->db->lastInsertId());
    }

    public function getTaskById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM photo_tasks WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) throw new RuntimeException("Photo task not found: {$id}");

        $row['source_urls'] = $this->decodeJson($row['source_urls_json'] ?? null);
        $row['params'] = $this->decodeJson($row['params_json'] ?? null);
        unset($row['source_urls_json'], $row['params_json']);

        return $row;
    }

    public function listTasks(array $filters): array
    {
        $sql = "SELECT * FROM photo_tasks WHERE 1=1";
        $params = [];

        if ($filters['status']) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if ($filters['mode']) {
            $sql .= " AND mode = :mode";
            $params['mode'] = $filters['mode'];
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
            $r['source_urls'] = $this->decodeJson($r['source_urls_json'] ?? null);
            $r['params'] = $this->decodeJson($r['params_json'] ?? null);
            unset($r['source_urls_json'], $r['params_json']);
        }
        return $rows;
    }

    public function updateTaskStatus(
        int $taskId,
        string $status,
        ?string $errorCode = null,
        ?string $errorMessage = null
    ): array {
        $stmt = $this->db->prepare("
            UPDATE photo_tasks
               SET status = :s,
                   error_code = :ec,
                   error_message = :em,
                   finished_at = CASE WHEN :s IN ('done','failed') THEN NOW() ELSE finished_at END,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            's' => $status,
            'ec' => $errorCode,
            'em' => $errorMessage,
            'id' => $taskId,
        ]);

        return $this->getTaskById($taskId);
    }

    public function incrementAttempts(int $taskId): void
    {
        $stmt = $this->db->prepare("
            UPDATE photo_tasks
               SET attempts = attempts + 1, updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['id' => $taskId]);
    }

    // -------- Photos (artifacts) --------

    public function addPhotos(int $cardId, array $photos): array
    {
        if (!$photos) return [];

        $this->db->beginTransaction();
        try {
            $ins = $this->db->prepare("
                INSERT INTO photos(card_id, url, width, height, kind, meta_json, is_primary, created_at)
                VALUES (:cid, :url, :w, :h, :k, :m, :p, NOW())
            ");

            foreach ($photos as $p) {
                $ins->execute([
                    'cid' => $cardId,
                    'url' => $p['url'],
                    'w' => $p['width'],
                    'h' => $p['height'],
                    'k' => $p['kind'],
                    'm' => json_encode($p['meta'] ?? [], JSON_UNESCAPED_UNICODE),
                    'p' => 0,
                ]);
            }

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new RuntimeException("Failed to add photos: {$e->getMessage()}");
        }

        return $this->listCardPhotos($cardId);
    }

    public function listCardPhotos(int $cardId): array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM photos
             WHERE card_id = :cid
             ORDER BY is_primary DESC, id ASC
        ");
        $stmt->execute(['cid' => $cardId]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];

        foreach ($rows as &$r) {
            $r['meta'] = $this->decodeJson($r['meta_json'] ?? null);
            unset($r['meta_json']);
        }
        return $rows;
    }

    public function deletePhoto(int $photoId): void
    {
        $stmt = $this->db->prepare("DELETE FROM photos WHERE id = :id");
        $stmt->execute(['id' => $photoId]);
    }

    public function setPrimaryPhoto(int $cardId, int $photoId): void
    {
        $this->db->beginTransaction();
        try {
            $this->db->prepare("UPDATE photos SET is_primary=0 WHERE card_id=:cid")
                ->execute(['cid' => $cardId]);

            $this->db->prepare("
                UPDATE photos SET is_primary=1 WHERE id=:pid AND card_id=:cid
            ")->execute(['pid' => $photoId, 'cid' => $cardId]);

            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw new RuntimeException("Failed to set primary photo");
        }
    }

    // -------- Cards payload bridge --------

    public function attachPhotosToCardPayload(int $cardId): void
    {
        // Берём все фото url'ы и кладём в payload.photos
        $photos = $this->listCardPhotos($cardId);
        $urls = array_map(fn($p) => $p['url'], $photos);

        $cardStmt = $this->db->prepare("SELECT payload_json FROM cards WHERE id=:id LIMIT 1");
        $cardStmt->execute(['id' => $cardId]);
        $row = $cardStmt->fetch(PDO::FETCH_ASSOC);

        $payload = $this->decodeJson($row['payload_json'] ?? null);
        $payload['photos'] = $urls;

        $upd = $this->db->prepare("
            UPDATE cards
               SET payload_json = :p, updated_at = NOW()
             WHERE id = :id
        ");
        $upd->execute([
            'p' => json_encode($payload, JSON_UNESCAPED_UNICODE),
            'id' => $cardId,
        ]);
    }

    // -------- Audit --------

    public function writeAudit(?int $userId, string $action, string $message, string $level='info'): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs(ts, level, message, correlation_id, user_id, action, source)
                VALUES (UNIX_TIMESTAMP(), :lvl, :msg, NULL, :uid, :act, 'photos')
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
