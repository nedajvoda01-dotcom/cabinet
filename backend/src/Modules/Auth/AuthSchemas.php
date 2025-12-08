<?php
declare(strict_types=1);

namespace Backend\Modules\Auth;

use InvalidArgumentException;

/**
 * AuthSchemas
 *
 * DTO + валидация формы данных (без бизнес-правил).
 */
final class AuthSchemas
{
    // ---------- helpers ----------

    public static function validate(array $data, array $required = [], array $types = []): array
    {
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Missing required field: {$key}");
            }
        }

        foreach ($types as $key => $type) {
            if (!array_key_exists($key, $data)) continue;
            $v = $data[$key];
            $ok = match ($type) {
                'int' => is_int($v) || (is_string($v) && ctype_digit($v)),
                'string' => is_string($v),
                'bool' => is_bool($v) || $v === 0 || $v === 1 || $v === '0' || $v === '1',
                'array' => is_array($v),
                default => true,
            };
            if (!$ok) {
                throw new InvalidArgumentException("Invalid type for {$key}, expected {$type}");
            }
        }
        return $data;
    }

    private static function toInt(mixed $v, int $default = 0): int
    {
        if ($v === null || $v === '') return $default;
        if (is_int($v)) return $v;
        if (is_string($v) && ctype_digit($v)) return (int)$v;
        throw new InvalidArgumentException("Expected int");
    }

    // ---------- login / refresh / logout ----------

    public static function toLoginDto(array $body): array
    {
        self::validate($body, ['email', 'password'], [
            'email' => 'string',
            'password' => 'string',
            'remember' => 'bool',
        ]);

        $email = strtolower(trim($body['email']));
        if ($email === '' || !str_contains($email, '@')) {
            throw new InvalidArgumentException("Invalid email");
        }

        $password = (string)$body['password'];
        if (strlen($password) < 6) {
            throw new InvalidArgumentException("Password too short");
        }

        return [
            'email' => $email,
            'password' => $password,
            'remember' => (bool)($body['remember'] ?? false),
        ];
    }

    public static function toRefreshDto(array $body): array
    {
        self::validate($body, ['refresh_token'], ['refresh_token' => 'string']);
        $rt = trim((string)$body['refresh_token']);
        if ($rt === '') throw new InvalidArgumentException("Empty refresh_token");
        return ['refresh_token' => $rt];
    }

    public static function toLogoutDto(array $body): array
    {
        self::validate($body, ['refresh_token'], ['refresh_token' => 'string']);
        $rt = trim((string)$body['refresh_token']);
        if ($rt === '') throw new InvalidArgumentException("Empty refresh_token");
        return ['refresh_token' => $rt];
    }

    // ---------- password reset ----------

    public static function toRequestResetDto(array $body): array
    {
        self::validate($body, ['email'], ['email' => 'string']);
        $email = strtolower(trim($body['email']));
        if ($email === '' || !str_contains($email, '@')) {
            throw new InvalidArgumentException("Invalid email");
        }
        return ['email' => $email];
    }

    public static function toConfirmResetDto(array $body): array
    {
        self::validate($body, ['token', 'password'], [
            'token' => 'string',
            'password' => 'string',
        ]);

        $token = trim((string)$body['token']);
        if ($token === '') throw new InvalidArgumentException("Empty token");

        $password = (string)$body['password'];
        if (strlen($password) < 6) {
            throw new InvalidArgumentException("Password too short");
        }

        return [
            'token' => $token,
            'password' => $password
        ];
    }

    // ---------- pagination etc (на будущее) ----------

    public static function toIdDto(string|int $id): array
    {
        return ['id' => self::toInt($id, 0)];
    }

    // ---------- response wrappers ----------

    public static function ok(array $data = []): array
    {
        return ['ok' => true, 'data' => $data];
    }

    public static function fail(string $message, ?string $code = null): array
    {
        $err = ['message' => $message];
        if ($code) $err['code'] = $code;
        return ['ok' => false, 'error' => $err];
    }
}
