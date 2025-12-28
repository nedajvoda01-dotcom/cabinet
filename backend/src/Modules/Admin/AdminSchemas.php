<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

use Backend\Application\Contracts\TraceContext;
use InvalidArgumentException;

/**
 * AdminSchemas
 *
 * Форматные DTO и валидация **формы** данных.
 * Бизнес-валидация — в AdminService.
 */
final class AdminSchemas
{
    // --------- helpers ---------

    public static function validate(array $data, array $required = [], array $types = []): array
    {
        foreach ($required as $key) {
            if (!array_key_exists($key, $data)) {
                throw new InvalidArgumentException("Missing required field: {$key}");
            }
        }

        foreach ($types as $key => $type) {
            if (!array_key_exists($key, $data)) {
                continue;
            }
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

    private static function toBool(mixed $v, bool $default = false): bool
    {
        if ($v === null || $v === '') return $default;
        if (is_bool($v)) return $v;
        if ($v === 1 || $v === '1') return true;
        if ($v === 0 || $v === '0') return false;
        throw new InvalidArgumentException("Expected bool");
    }

    // --------- Queues ---------

    public static function toListQueuesDto(array $query): array
    {
        // Сейчас фильтров нет, оставляем на будущее
        return [
            'with_jobs' => self::toBool($query['with_jobs'] ?? null, false),
        ];
    }

    public static function toQueueTypeDto(string $type): array
    {
        $type = trim($type);
        if ($type === '') {
            throw new InvalidArgumentException("Queue type is empty");
        }
        return ['type' => $type];
    }

    public static function toListQueueJobsDto(string $type, array $query): array
    {
        return [
            'type' => self::toQueueTypeDto($type)['type'],
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'status' => isset($query['status']) ? (string)$query['status'] : null,
        ];
    }

    // --------- DLQ ---------

    public static function toListDlqDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'type' => isset($query['type']) ? (string)$query['type'] : null,
        ];
    }

    public static function toDlqIdDto(string|int $id): array
    {
        $idInt = self::toInt($id, 0);
        if ($idInt <= 0) {
            throw new InvalidArgumentException("Invalid DLQ id");
        }
        return ['id' => $idInt];
    }

    public static function toBulkRetryDlqDto(array $body): array
    {
        self::validate($body, [], [
            'type' => 'string',
            'limit' => 'int',
        ]);

        return [
            'type' => $body['type'] ?? null,
            'limit' => isset($body['limit']) ? self::toInt($body['limit'], 0) : null,
        ];
    }

    // --------- Logs ---------

    public static function toListLogsDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 100),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'user_id' => isset($query['user_id']) ? self::toInt($query['user_id'], 0) : null,
            'card_id' => isset($query['card_id']) ? self::toInt($query['card_id'], 0) : null,
            'action' => isset($query['action']) ? (string)$query['action'] : null,
            'from_ts' => isset($query['from_ts']) ? self::toInt($query['from_ts'], 0) : null,
            'to_ts' => isset($query['to_ts']) ? self::toInt($query['to_ts'], 0) : null,
        ];
    }

    // --------- Users / Roles ---------

    public static function toListUsersDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'q' => isset($query['q']) ? (string)$query['q'] : null,
            'role' => isset($query['role']) ? (string)$query['role'] : null,
        ];
    }

    public static function toUpdateUserRolesDto(array $body): array
    {
        self::validate($body, ['roles'], ['roles' => 'array']);
        $roles = $body['roles'];

        foreach ($roles as $r) {
            if (!is_string($r) || trim($r) === '') {
                throw new InvalidArgumentException("Invalid role value");
            }
        }

        return ['roles' => array_values(array_unique($roles))];
    }

    // --------- Response wrappers ---------

    public static function ok(array $data = []): array
    {
        return ['ok' => true, 'data' => $data];
    }

    public static function fail(string $message, ?string $code = null): array
    {
        $err = ['message' => $message];
        if ($code) $err['code'] = $code;
        return ['ok' => false, 'error' => $err, 'traceId' => TraceContext::ensure()->traceId()];
    }
}
