<?php
declare(strict_types=1);

namespace Backend\Modules\Export;

use PDO;
use PDOException;
use RuntimeException;

/**
 * ExportModel
 *
 * Единственная точка знания о таблицах/хранилищах.
 *
 * Дефолтная таблица:
 *  - exports (
 *      id, user_id, type, format, params_json,
 *      status, progress, file_path, file_url,
 *      error_code, error_message,
 *      created_at, updated_at, finished_at
 *    )
 *  - audit_logs (см Admin/Auth)
 */
final class ExportModel
{
    public function __construct(private PDO $db) {}

    // -------- CRUD / queries --------

    public function createExport(int $userId, string $type, string $format, array $params): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO exports(user_id, type, format, params_json, status, progress, created_at, updated_at)
            VALUES (:uid, :type, :fmt, :params, 'queued', 0, NOW(), NOW())
        ");
        $stmt->execute([
            'uid' => $userId,
            'type' => $type,
            'fmt' => $format,
            'params' => json_encode($params, JSON_UNESCAPED_UNICODE),
        ]);

        return $this->getExportById((int)$this->db->lastInsertId());
    }

    public function getExportById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM exports WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) throw new RuntimeException("Export not found: {$id}");

        $row['params'] = $this->decodeJson($row['params_json'] ?? null);
        unset($row['params_json']);

        return $row;
    }

    public function listExports(array $filters): array
    {
        $sql = "SELECT * FROM exports WHERE 1=1";
        $params = [];

        if ($filters['type']) {
            $sql .= " AND type = :type";
            $params['type'] = $filters['type'];
        }
        if ($filters['status']) {
            $sql .= " AND status = :status";
            $params['status'] = $filters['status'];
        }
        if ($filters['user_id']) {
            $sql .= " AND user_id = :uid";
            $params['uid'] = $filters['user_id'];
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

    // -------- Status / progress --------

    public function updateExportStatus(int $id, string $status, ?string $errorCode = null, ?string $errorMessage = null): array
    {
        $stmt = $this->db->prepare("
            UPDATE exports
               SET status = :s,
                   error_code = :ec,
                   error_message = :em,
                   finished_at = CASE
                       WHEN :s IN ('done','failed','canceled') THEN NOW()
                       ELSE finished_at
                   END,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            's' => $status,
            'ec' => $errorCode,
            'em' => $errorMessage,
            'id' => $id,
        ]);

        return $this->getExportById($id);
    }

    public function updateExportProgress(int $id, int $progress): void
    {
        $progress = max(0, min(100, $progress));
        $stmt = $this->db->prepare("
            UPDATE exports
               SET progress = :p, updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['p' => $progress, 'id' => $id]);
    }

    public function setExportFile(int $id, string $filePath, ?string $fileUrl = null): array
    {
        $stmt = $this->db->prepare("
            UPDATE exports
               SET file_path = :fp,
                   file_url = :fu,
                   updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute([
            'fp' => $filePath,
            'fu' => $fileUrl,
            'id' => $id,
        ]);

        return $this->getExportById($id);
    }

    // -------- Audit --------

    public function writeAudit(?int $userId, string $action, string $message, string $level = 'info'): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs(ts, level, message, correlation_id, user_id, action, source)
                VALUES (UNIX_TIMESTAMP(), :lvl, :msg, NULL, :uid, :act, 'export')
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
