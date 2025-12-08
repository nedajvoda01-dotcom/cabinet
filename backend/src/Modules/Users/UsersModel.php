<?php
declare(strict_types=1);

namespace Backend\Modules\Users;

use PDO;
use RuntimeException;

final class UsersModel
{
    public function __construct(private PDO $db) {}

    // ---------- Users ----------

    public function createUser(string $email, string $name, ?string $passwordHash, bool $isActive): array
    {
        $stmt = $this->db->prepare("
            INSERT INTO users(email, name, password_hash, is_active, created_at, updated_at)
            VALUES (:email, :name, :ph, :active, NOW(), NOW())
        ");
        $stmt->execute([
            'email' => $email,
            'name' => $name,
            'ph' => $passwordHash,
            'active' => $isActive ? 1 : 0,
        ]);

        return $this->getUserById((int)$this->db->lastInsertId());
    }

    public function getUserById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id=:id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u) throw new RuntimeException("User not found: {$id}");
        $u['is_active'] = (bool)$u['is_active'];
        $u['roles'] = $this->getUserRoles($id);
        return $u;
    }

    public function getUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email=:e LIMIT 1");
        $stmt->execute(['e' => $email]);
        $u = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$u) return null;
        $u['is_active'] = (bool)$u['is_active'];
        $u['roles'] = $this->getUserRoles((int)$u['id']);
        return $u;
    }

    public function listUsers(array $filters): array
    {
        $sql = "SELECT u.* FROM users u";
        $params = [];

        if ($filters['role_code']) {
            $sql .= " JOIN users_roles ur ON ur.user_id=u.id
                      JOIN roles r ON r.id=ur.role_id";
        }

        $sql .= " WHERE 1=1";

        if ($filters['q']) {
            $sql .= " AND (LOWER(u.email) LIKE :q OR LOWER(u.name) LIKE :q)";
            $params['q'] = '%' . strtolower($filters['q']) . '%';
        }
        if ($filters['is_active'] !== null) {
            $sql .= " AND u.is_active = :ia";
            $params['ia'] = $filters['is_active'] ? 1 : 0;
        }
        if ($filters['role_code']) {
            $sql .= " AND r.code = :rc";
            $params['rc'] = $filters['role_code'];
        }

        $sql .= " ORDER BY u.id DESC LIMIT :limit OFFSET :offset";

        $stmt = $this->db->prepare($sql);
        foreach ($params as $k => $v) $stmt->bindValue($k, $v);
        $stmt->bindValue('limit', (int)$filters['limit'], PDO::PARAM_INT);
        $stmt->bindValue('offset', (int)$filters['offset'], PDO::PARAM_INT);
        $stmt->execute();

        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($rows as &$u) {
            $u['is_active'] = (bool)$u['is_active'];
            $u['roles'] = $this->getUserRoles((int)$u['id']);
        }
        return $rows;
    }

    public function updateUser(int $id, array $patch): array
    {
        if (!$patch) return $this->getUserById($id);

        $fields = [];
        $params = ['id' => $id];
        foreach ($patch as $k => $v) {
            if (!in_array($k, ['name','password_hash','is_active'], true)) continue;
            $fields[] = "{$k}=:{$k}";
            $params[$k] = ($k === 'is_active') ? ($v ? 1 : 0) : $v;
        }

        if ($fields) {
            $sql = "UPDATE users SET " . implode(',', $fields) . ", updated_at=NOW() WHERE id=:id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
        }

        return $this->getUserById($id);
    }

    public function deleteUser(int $id): void
    {
        // soft-delete can be added later; MVP hard delete allowed by spec
        $this->db->prepare("DELETE FROM users_roles WHERE user_id=:id")->execute(['id'=>$id]);
        $this->db->prepare("DELETE FROM users WHERE id=:id")->execute(['id'=>$id]);
    }

    // ---------- Roles ----------

    public function getRoleById(int $id): array
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE id=:id LIMIT 1");
        $stmt->execute(['id'=>$id]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) throw new RuntimeException("Role not found: {$id}");
        $r['permissions'] = json_decode($r['permissions_json'] ?? '[]', true) ?: [];
        unset($r['permissions_json']);
        return $r;
    }

    public function getRoleByCode(string $code): array
    {
        $stmt = $this->db->prepare("SELECT * FROM roles WHERE code=:c LIMIT 1");
        $stmt->execute(['c'=>$code]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) throw new RuntimeException("Role not found: {$code}");
        $r['permissions'] = json_decode($r['permissions_json'] ?? '[]', true) ?: [];
        unset($r['permissions_json']);
        return $r;
    }

    public function getUserRoles(int $userId): array
    {
        $stmt = $this->db->prepare("
            SELECT r.* FROM roles r
            JOIN users_roles ur ON ur.role_id=r.id
            WHERE ur.user_id=:uid
            ORDER BY r.id ASC
        ");
        $stmt->execute(['uid'=>$userId]);
        $roles = $stmt->fetchAll(PDO::FETCH_ASSOC) ?: [];
        foreach ($roles as &$r) {
            $r['permissions'] = json_decode($r['permissions_json'] ?? '[]', true) ?: [];
            unset($r['permissions_json']);
        }
        return $roles;
    }

    public function assignRole(int $userId, int $roleId): void
    {
        $stmt = $this->db->prepare("
            INSERT IGNORE INTO users_roles(user_id, role_id, assigned_at)
            VALUES (:uid, :rid, NOW())
        ");
        $stmt->execute(['uid'=>$userId, 'rid'=>$roleId]);
    }

    public function revokeRole(int $userId, int $roleId): void
    {
        $stmt = $this->db->prepare("
            DELETE FROM users_roles WHERE user_id=:uid AND role_id=:rid
        ");
        $stmt->execute(['uid'=>$userId, 'rid'=>$roleId]);
    }

    // ---------- Audit (optional) ----------

    public function writeAudit(?int $actorId, string $action, string $entityType, int $entityId, array $before, array $after): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs(actor_user_id, action, entity_ref_type, entity_ref_id, before_json, after_json, correlation_id, created_at)
                VALUES (:aid, :act, :t, :id, :b, :a, NULL, NOW())
            ");
            $stmt->execute([
                'aid'=>$actorId,
                'act'=>$action,
                't'=>$entityType,
                'id'=>$entityId,
                'b'=>json_encode($before, JSON_UNESCAPED_UNICODE),
                'a'=>json_encode($after, JSON_UNESCAPED_UNICODE),
            ]);
        } catch (\Throwable $e) {
            // audit is best-effort
        }
    }
}
