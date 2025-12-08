<?php
declare(strict_types=1);

namespace Backend\Modules\Auth;

use PDO;
use PDOException;
use RuntimeException;

/**
 * AuthModel
 *
 * Единственная точка знания про таблицы/поля.
 *
 * Дефолтные таблицы:
 *  - users (id, email, password_hash, name, is_active, created_at, updated_at)
 *  - sessions (id, user_id, refresh_hash, user_agent, ip, expires_at, revoked_at, created_at)
 *  - password_resets (id, user_id, token_hash, expires_at, used_at, created_at)
 *  - audit_logs (id, ts, level, message, correlation_id, user_id, action, source)
 */
final class AuthModel
{
    public function __construct(private PDO $db) {}

    // ---------- Users ----------

    public function findUserByEmail(string $email): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE email = :email LIMIT 1");
        $stmt->execute(['email' => $email]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function findUserById(int $id): ?array
    {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function updateUserPasswordHash(int $userId, string $hash): void
    {
        $stmt = $this->db->prepare("
            UPDATE users
               SET password_hash = :h, updated_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['h' => $hash, 'id' => $userId]);
    }

    public function getUserRoles(int $userId): array
    {
        try {
            $stmt = $this->db->prepare("SELECT role FROM users_roles WHERE user_id = :id");
            $stmt->execute(['id' => $userId]);
            return array_map(fn($r) => $r['role'], $stmt->fetchAll(PDO::FETCH_ASSOC) ?: []);
        } catch (PDOException) {
            return [];
        }
    }

    // ---------- Sessions / Refresh tokens ----------

    public function createSession(
        int $userId,
        string $refreshHash,
        string $userAgent,
        string $ip,
        int $expiresAt
    ): int {
        $stmt = $this->db->prepare("
            INSERT INTO sessions(user_id, refresh_hash, user_agent, ip, expires_at, created_at)
            VALUES (:uid, :rh, :ua, :ip, FROM_UNIXTIME(:exp), NOW())
        ");
        $stmt->execute([
            'uid' => $userId,
            'rh' => $refreshHash,
            'ua' => $userAgent,
            'ip' => $ip,
            'exp' => $expiresAt,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findSessionByRefreshHash(string $refreshHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM sessions
             WHERE refresh_hash = :rh
               AND revoked_at IS NULL
               AND expires_at > NOW()
             LIMIT 1
        ");
        $stmt->execute(['rh' => $refreshHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function revokeSession(int $sessionId): void
    {
        $stmt = $this->db->prepare("
            UPDATE sessions
               SET revoked_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['id' => $sessionId]);
    }

    public function revokeAllUserSessions(int $userId): void
    {
        $stmt = $this->db->prepare("
            UPDATE sessions
               SET revoked_at = NOW()
             WHERE user_id = :uid AND revoked_at IS NULL
        ");
        $stmt->execute(['uid' => $userId]);
    }

    // ---------- Password resets ----------

    public function createPasswordReset(int $userId, string $tokenHash, int $expiresAt): int
    {
        $stmt = $this->db->prepare("
            INSERT INTO password_resets(user_id, token_hash, expires_at, created_at)
            VALUES (:uid, :th, FROM_UNIXTIME(:exp), NOW())
        ");
        $stmt->execute([
            'uid' => $userId,
            'th' => $tokenHash,
            'exp' => $expiresAt,
        ]);

        return (int)$this->db->lastInsertId();
    }

    public function findPasswordResetByTokenHash(string $tokenHash): ?array
    {
        $stmt = $this->db->prepare("
            SELECT * FROM password_resets
             WHERE token_hash = :th
               AND used_at IS NULL
               AND expires_at > NOW()
             LIMIT 1
        ");
        $stmt->execute(['th' => $tokenHash]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    public function markPasswordResetUsed(int $resetId): void
    {
        $stmt = $this->db->prepare("
            UPDATE password_resets
               SET used_at = NOW()
             WHERE id = :id
        ");
        $stmt->execute(['id' => $resetId]);
    }

    // ---------- Audit ----------

    public function writeAudit(int $userId, string $action, string $message, string $level = 'info'): void
    {
        try {
            $stmt = $this->db->prepare("
                INSERT INTO audit_logs(ts, level, message, correlation_id, user_id, action, source)
                VALUES (UNIX_TIMESTAMP(), :lvl, :msg, NULL, :uid, :act, 'auth')
            ");
            $stmt->execute([
                'lvl' => $level,
                'msg' => $message,
                'uid' => $userId,
                'act' => $action,
            ]);
        } catch (PDOException) {
            // аудит не должен ломать аутентификацию
        }
    }
}
