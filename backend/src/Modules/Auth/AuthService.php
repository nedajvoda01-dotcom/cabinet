<?php
declare(strict_types=1);

namespace Backend\Modules\Auth;

use RuntimeException;

/**
 * AuthService
 *
 * Бизнес-логика Auth:
 *  - login -> access+refresh
 *  - refresh -> новый access
 *  - logout -> revoke refresh
 *  - me -> профиль
 *  - password reset request/confirm
 *  - JWT utilities
 */
final class AuthService
{
    private const ACCESS_TTL_SEC = 60 * 15;      // 15 минут
    private const REFRESH_TTL_SEC = 60 * 60 * 24 * 30; // 30 дней
    private const RESET_TTL_SEC = 60 * 30;      // 30 минут

    public function __construct(
        private AuthModel $model,
        private AuthJobs $jobs,
        private string $jwtSecret,  // инжектится из конфигурации
        private string $jwtIssuer = 'autocontent'
    ) {}

    // ---------- Public API ----------

    public function login(array $dto, string $userAgent, string $ip): array
    {
        $user = $this->model->findUserByEmail($dto['email']);
        if (!$user || empty($user['password_hash'])) {
            throw new RuntimeException("Invalid credentials");
        }
        if (!empty($user['is_active']) && (int)$user['is_active'] === 0) {
            throw new RuntimeException("User disabled");
        }

        if (!password_verify($dto['password'], (string)$user['password_hash'])) {
            $this->model->writeAudit((int)$user['id'], 'login_failed', 'Invalid password', 'warn');
            throw new RuntimeException("Invalid credentials");
        }

        $userId = (int)$user['id'];
        $roles = $this->model->getUserRoles($userId);

        $now = time();
        $accessExp = $now + self::ACCESS_TTL_SEC;
        $refreshExp = $now + ($dto['remember'] ? self::REFRESH_TTL_SEC * 2 : self::REFRESH_TTL_SEC);

        $accessToken = $this->signJwt([
            'sub' => $userId,
            'roles' => $roles,
            'iat' => $now,
            'exp' => $accessExp,
            'iss' => $this->jwtIssuer,
            'typ' => 'access',
        ]);

        $refreshToken = $this->randomToken(48);
        $refreshHash = hash('sha256', $refreshToken);

        $sessionId = $this->model->createSession($userId, $refreshHash, $userAgent, $ip, $refreshExp);

        $this->model->writeAudit($userId, 'login', 'User logged in');

        return [
            'access_token' => $accessToken,
            'access_expires_at' => $accessExp,
            'refresh_token' => $refreshToken,
            'refresh_expires_at' => $refreshExp,
            'session_id' => $sessionId,
            'user' => $this->publicUser($user, $roles),
        ];
    }

    public function refresh(array $dto): array
    {
        $refreshHash = hash('sha256', $dto['refresh_token']);
        $session = $this->model->findSessionByRefreshHash($refreshHash);
        if (!$session) {
            throw new RuntimeException("Invalid refresh token");
        }

        $userId = (int)$session['user_id'];
        $user = $this->model->findUserById($userId);
        if (!$user) throw new RuntimeException("User not found");

        $roles = $this->model->getUserRoles($userId);

        $now = time();
        $accessExp = $now + self::ACCESS_TTL_SEC;

        $accessToken = $this->signJwt([
            'sub' => $userId,
            'roles' => $roles,
            'iat' => $now,
            'exp' => $accessExp,
            'iss' => $this->jwtIssuer,
            'typ' => 'access',
        ]);

        return [
            'access_token' => $accessToken,
            'access_expires_at' => $accessExp,
            'user' => $this->publicUser($user, $roles),
        ];
    }

    public function logout(array $dto): void
    {
        $refreshHash = hash('sha256', $dto['refresh_token']);
        $session = $this->model->findSessionByRefreshHash($refreshHash);
        if (!$session) return;

        $this->model->revokeSession((int)$session['id']);
        $this->model->writeAudit((int)$session['user_id'], 'logout', 'User logged out');
    }

    public function me(int $userId): array
    {
        $user = $this->model->findUserById($userId);
        if (!$user) throw new RuntimeException("User not found");

        $roles = $this->model->getUserRoles($userId);
        return $this->publicUser($user, $roles);
    }

    // ---------- Password reset ----------

    public function requestPasswordReset(array $dto): void
    {
        $user = $this->model->findUserByEmail($dto['email']);
        if (!$user) {
            // не светим существование email
            return;
        }

        $userId = (int)$user['id'];

        $token = $this->randomToken(32);
        $tokenHash = hash('sha256', $token);
        $exp = time() + self::RESET_TTL_SEC;

        $this->model->createPasswordReset($userId, $tokenHash, $exp);

        $this->jobs->dispatchPasswordResetEmail($dto['email'], $token);
        $this->model->writeAudit($userId, 'password_reset_request', 'Password reset requested');
    }

    public function confirmPasswordReset(array $dto): void
    {
        $tokenHash = hash('sha256', $dto['token']);
        $reset = $this->model->findPasswordResetByTokenHash($tokenHash);
        if (!$reset) throw new RuntimeException("Invalid or expired token");

        $userId = (int)$reset['user_id'];
        $newHash = password_hash($dto['password'], PASSWORD_DEFAULT);

        $this->model->updateUserPasswordHash($userId, $newHash);
        $this->model->markPasswordResetUsed((int)$reset['id']);

        // на всякий пожарный — ревок всех refresh
        $this->model->revokeAllUserSessions($userId);

        $this->model->writeAudit($userId, 'password_reset_confirm', 'Password reset confirmed');
    }

    // ---------- JWT helpers ----------

    public function verifyAccessToken(string $jwt): array
    {
        $payload = $this->verifyJwt($jwt);
        if (($payload['typ'] ?? null) !== 'access') {
            throw new RuntimeException("Invalid token type");
        }
        return $payload;
    }

    // ---------- internals ----------

    private function publicUser(array $row, array $roles): array
    {
        return [
            'id' => (int)$row['id'],
            'email' => (string)$row['email'],
            'name' => (string)($row['name'] ?? ''),
            'roles' => $roles,
        ];
    }

    private function randomToken(int $len): string
    {
        return rtrim(strtr(base64_encode(random_bytes($len)), '+/', '-_'), '=');
    }

    private function signJwt(array $payload): string
    {
        $header = ['alg' => 'HS256', 'typ' => 'JWT'];
        $segments = [
            $this->b64(json_encode($header, JSON_UNESCAPED_SLASHES)),
            $this->b64(json_encode($payload, JSON_UNESCAPED_SLASHES)),
        ];
        $sig = hash_hmac('sha256', implode('.', $segments), $this->jwtSecret, true);
        $segments[] = $this->b64($sig);
        return implode('.', $segments);
    }

    private function verifyJwt(string $jwt): array
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) throw new RuntimeException("Malformed JWT");

        [$h64, $p64, $s64] = $parts;
        $sig = $this->b64d($s64);
        $check = hash_hmac('sha256', "{$h64}.{$p64}", $this->jwtSecret, true);

        if (!hash_equals($check, $sig)) throw new RuntimeException("Bad signature");

        $payload = json_decode($this->b64d($p64), true);
        if (!is_array($payload)) throw new RuntimeException("Bad payload");

        $now = time();
        if (isset($payload['exp']) && $now >= (int)$payload['exp']) {
            throw new RuntimeException("Token expired");
        }
        if (($payload['iss'] ?? null) !== $this->jwtIssuer) {
            throw new RuntimeException("Bad issuer");
        }

        return $payload;
    }

    private function b64(string $s): string
    {
        return rtrim(strtr(base64_encode($s), '+/', '-_'), '=');
    }

    private function b64d(string $s): string
    {
        return base64_decode(strtr($s, '-_', '+/'));
    }
}
