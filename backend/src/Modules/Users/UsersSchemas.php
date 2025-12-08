<?php
declare(strict_types=1);

namespace Backend\Modules\Users;

use InvalidArgumentException;

final class UsersSchemas
{
    private static function validate(array $data, array $required = [], array $types = []): array
    {
        foreach ($required as $k) {
            if (!array_key_exists($k, $data)) {
                throw new InvalidArgumentException("Missing required field: {$k}");
            }
        }
        foreach ($types as $k => $t) {
            if (!array_key_exists($k, $data)) continue;
            $v = $data[$k];
            $ok = match ($t) {
                'int' => is_int($v) || (is_string($v) && ctype_digit($v)),
                'string' => is_string($v),
                'bool' => is_bool($v) || $v === 0 || $v === 1 || $v === '0' || $v === '1',
                'array' => is_array($v),
                default => true,
            };
            if (!$ok) throw new InvalidArgumentException("Invalid type for {$k}, expected {$t}");
        }
        return $data;
    }

    private static function toInt(mixed $v): int
    {
        if (is_int($v)) return $v;
        if (is_string($v) && ctype_digit($v)) return (int)$v;
        throw new InvalidArgumentException("Expected int");
    }

    private static function toBool(mixed $v): bool
    {
        if (is_bool($v)) return $v;
        if ($v === 1 || $v === '1') return true;
        if ($v === 0 || $v === '0') return false;
        throw new InvalidArgumentException("Expected bool");
    }

    private static function toStrOrNull(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    // ---------- DTOs ----------

    public static function toIdDto(string|int $id): array
    {
        $id = self::toInt($id);
        if ($id <= 0) throw new InvalidArgumentException("Invalid id");
        return ['id' => $id];
    }

    public static function toListUsersDto(array $query): array
    {
        return [
            'limit' => isset($query['limit']) ? self::toInt($query['limit']) : 50,
            'offset' => isset($query['offset']) ? self::toInt($query['offset']) : 0,
            'q' => self::toStrOrNull($query['q'] ?? null),
            'is_active' => isset($query['is_active']) ? self::toBool($query['is_active']) : null,
            'role_code' => self::toStrOrNull($query['role_code'] ?? null),
        ];
    }

    /**
     * POST /users
     * email, name, password (optional), is_active(optional)
     */
    public static function toCreateUserDto(array $body): array
    {
        self::validate($body, ['email', 'name'], [
            'email' => 'string',
            'name' => 'string',
            'password' => 'string',
            'is_active' => 'bool',
        ]);

        $email = strtolower(trim($body['email']));
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException("Invalid email");
        }

        $name = trim($body['name']);
        if ($name === '') throw new InvalidArgumentException("Empty name");

        return [
            'email' => $email,
            'name' => $name,
            'password' => self::toStrOrNull($body['password'] ?? null),
            'is_active' => isset($body['is_active']) ? self::toBool($body['is_active']) : true,
        ];
    }

    /**
     * PATCH /users/:id
     * allow: name, password, is_active
     */
    public static function toUpdateUserDto(array $body): array
    {
        self::validate($body, [], [
            'name' => 'string',
            'password' => 'string',
            'is_active' => 'bool',
        ]);

        return array_filter([
            'name' => self::toStrOrNull($body['name'] ?? null),
            'password' => self::toStrOrNull($body['password'] ?? null),
            'is_active' => array_key_exists('is_active', $body) ? self::toBool($body['is_active']) : null,
        ], fn($v) => $v !== null);
    }

    /**
     * POST /users/:id/roles/assign|revoke
     * body: { role_code? , role_id? }
     */
    public static function toRoleChangeDto(array $body): array
    {
        self::validate($body, [], [
            'role_code' => 'string',
            'role_id' => 'int',
        ]);

        $roleId = isset($body['role_id']) ? self::toInt($body['role_id']) : null;
        $roleCode = self::toStrOrNull($body['role_code'] ?? null);

        if ($roleId === null && $roleCode === null) {
            throw new InvalidArgumentException("role_id or role_code required");
        }

        return [
            'role_id' => $roleId,
            'role_code' => $roleCode ? strtolower($roleCode) : null,
        ];
    }

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
