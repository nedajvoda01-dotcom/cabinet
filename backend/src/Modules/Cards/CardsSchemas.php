<?php
declare(strict_types=1);

namespace Backend\Modules\Cards;

use InvalidArgumentException;

/**
 * CardsSchemas
 *
 * DTO + валидация формы данных.
 * Любые бизнес-ограничения (кто может, когда можно) — в CardsService.
 */
final class CardsSchemas
{
    // -------- helpers --------

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
                'json' => is_array($v) || is_object($v),
                default => true,
            };
            if (!$ok) throw new InvalidArgumentException("Invalid type for {$key}, expected {$type}");
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

    private static function toStringOrNull(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    // -------- Card CRUD DTOs --------

    public static function toCreateCardDto(array $body): array
    {
        self::validate($body, ['title'], [
            'title' => 'string',
            'description' => 'string',
            'payload' => 'json',
            'user_id' => 'int',
        ]);

        $title = trim((string)$body['title']);
        if ($title === '') throw new InvalidArgumentException("Empty title");

        return [
            'title' => $title,
            'description' => self::toStringOrNull($body['description'] ?? null),
            'payload' => $body['payload'] ?? [],
            'user_id' => isset($body['user_id']) ? self::toInt($body['user_id']) : null,
        ];
    }

    public static function toUpdateCardDto(array $body): array
    {
        self::validate($body, [], [
            'title' => 'string',
            'description' => 'string',
            'payload' => 'json',
            'status' => 'string',
            'is_locked' => 'bool',
        ]);

        $dto = [];
        if (array_key_exists('title', $body)) {
            $title = trim((string)$body['title']);
            if ($title === '') throw new InvalidArgumentException("Empty title");
            $dto['title'] = $title;
        }
        if (array_key_exists('description', $body)) {
            $dto['description'] = self::toStringOrNull($body['description']);
        }
        if (array_key_exists('payload', $body)) {
            $dto['payload'] = $body['payload'] ?? [];
        }
        if (array_key_exists('status', $body)) {
            $dto['status'] = trim((string)$body['status']);
        }
        if (array_key_exists('is_locked', $body)) {
            $dto['is_locked'] = self::toBool($body['is_locked']);
        }

        return $dto;
    }

    public static function toCardIdDto(string|int $id): array
    {
        $idInt = self::toInt($id);
        if ($idInt <= 0) throw new InvalidArgumentException("Invalid card id");
        return ['id' => $idInt];
    }

    // -------- List / search --------

    public static function toListCardsDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'q' => self::toStringOrNull($query['q'] ?? null),
            'status' => self::toStringOrNull($query['status'] ?? null),
            'user_id' => isset($query['user_id']) ? self::toInt($query['user_id']) : null,
            'locked' => isset($query['locked']) ? self::toBool($query['locked']) : null,
            'from_ts' => isset($query['from_ts']) ? self::toInt($query['from_ts']) : null,
            'to_ts' => isset($query['to_ts']) ? self::toInt($query['to_ts']) : null,
        ];
    }

    // -------- Status transitions --------

    public static function toTransitionDto(array $body): array
    {
        self::validate($body, ['action'], [
            'action' => 'string',
            'meta' => 'json',
        ]);
        $action = trim((string)$body['action']);
        if ($action === '') throw new InvalidArgumentException("Empty action");

        return [
            'action' => $action,
            'meta' => $body['meta'] ?? [],
        ];
    }

    public static function toBulkTransitionDto(array $body): array
    {
        self::validate($body, ['card_ids', 'action'], [
            'card_ids' => 'array',
            'action' => 'string',
            'meta' => 'json',
        ]);

        $ids = $body['card_ids'];
        if (!is_array($ids) || count($ids) === 0) {
            throw new InvalidArgumentException("card_ids must be non-empty array");
        }

        $cardIds = [];
        foreach ($ids as $id) {
            $cardIds[] = self::toCardIdDto($id)['id'];
        }

        $action = trim((string)$body['action']);
        if ($action === '') throw new InvalidArgumentException("Empty action");

        return [
            'card_ids' => array_values(array_unique($cardIds)),
            'action' => $action,
            'meta' => $body['meta'] ?? [],
        ];
    }

    // -------- Retry --------

    public static function toRetryDto(array $body): array
    {
        self::validate($body, [], [
            'reason' => 'string',
            'force' => 'bool',
        ]);

        return [
            'reason' => self::toStringOrNull($body['reason'] ?? null) ?? 'manual_retry',
            'force' => self::toBool($body['force'] ?? null, false),
        ];
    }

    // -------- Response wrappers --------

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
