<?php
declare(strict_types=1);

namespace Backend\Modules\Publish;

use InvalidArgumentException;

/**
 * PublishSchemas
 *
 * DTO + валидация формы данных.
 * Бизнес-валидация и SM-переходы — в PublishService.
 */
final class PublishSchemas
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

    // -------- Run publish --------
    /**
     * POST /publish/run
     * body:
     *  - card_id (required)
     *  - platform (required) e.g. "avito"
     *  - account_id (optional)
     *  - force (optional)
     *  - params (optional json)
     */
    public static function toRunPublishDto(array $body): array
    {
        self::validate($body, ['card_id', 'platform'], [
            'card_id' => 'int',
            'platform' => 'string',
            'account_id' => 'int',
            'force' => 'bool',
            'params' => 'json',
        ]);

        $cardId = self::toInt($body['card_id']);
        if ($cardId <= 0) throw new InvalidArgumentException("Invalid card_id");

        $platform = trim((string)$body['platform']);
        if ($platform === '') throw new InvalidArgumentException("Empty platform");

        return [
            'card_id' => $cardId,
            'platform' => strtolower($platform),
            'account_id' => isset($body['account_id']) ? self::toInt($body['account_id']) : null,
            'force' => self::toBool($body['force'] ?? null, false),
            'params' => $body['params'] ?? [],
        ];
    }

    // -------- List tasks --------

    public static function toListPublishTasksDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'status' => self::toStringOrNull($query['status'] ?? null),
            'platform' => self::toStringOrNull($query['platform'] ?? null),
            'account_id' => isset($query['account_id']) ? self::toInt($query['account_id']) : null,
            'card_id' => isset($query['card_id']) ? self::toInt($query['card_id']) : null,
            'from_ts' => isset($query['from_ts']) ? self::toInt($query['from_ts']) : null,
            'to_ts' => isset($query['to_ts']) ? self::toInt($query['to_ts']) : null,
        ];
    }

    public static function toTaskIdDto(string|int $id): array
    {
        $idInt = self::toInt($id);
        if ($idInt <= 0) throw new InvalidArgumentException("Invalid task id");
        return ['id' => $idInt];
    }

    // -------- Cancel / retry --------

    public static function toCancelDto(array $body): array
    {
        self::validate($body, [], ['reason' => 'string']);
        return [
            'reason' => self::toStringOrNull($body['reason'] ?? null) ?? 'manual_cancel',
        ];
    }

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

    // -------- Webhook result --------
    /**
     * POST /publish/webhook
     * body:
     *  - task_id (required)
     *  - card_id (required)
     *  - status: done|failed|blocked (required)
     *  - external_id (optional on done)
     *  - external_url (optional on done)
     *  - error_code / error_message (optional)
     */
    public static function toWebhookDto(array $body): array
    {
        self::validate($body, ['task_id', 'card_id', 'status'], [
            'task_id' => 'int',
            'card_id' => 'int',
            'status' => 'string',
            'external_id' => 'string',
            'external_url' => 'string',
            'error_code' => 'string',
            'error_message' => 'string',
        ]);

        $taskId = self::toInt($body['task_id']);
        $cardId = self::toInt($body['card_id']);
        $status = trim((string)$body['status']);

        if ($taskId <= 0) throw new InvalidArgumentException("Invalid task_id");
        if ($cardId <= 0) throw new InvalidArgumentException("Invalid card_id");
        if (!in_array($status, ['done', 'failed', 'blocked'], true)) {
            throw new InvalidArgumentException("Invalid status");
        }

        return [
            'task_id' => $taskId,
            'card_id' => $cardId,
            'status' => $status,
            'external_id' => self::toStringOrNull($body['external_id'] ?? null),
            'external_url' => self::toStringOrNull($body['external_url'] ?? null),
            'error_code' => self::toStringOrNull($body['error_code'] ?? null),
            'error_message' => self::toStringOrNull($body['error_message'] ?? null),
        ];
    }

    // -------- Metrics --------
    public static function toMetricsDto(array $query): array
    {
        return [
            'from_ts' => isset($query['from_ts']) ? self::toInt($query['from_ts']) : (time() - 3600 * 24),
            'to_ts' => isset($query['to_ts']) ? self::toInt($query['to_ts']) : time(),
            'platform' => self::toStringOrNull($query['platform'] ?? null),
            'account_id' => isset($query['account_id']) ? self::toInt($query['account_id']) : null,
            'bucket_sec' => self::toInt($query['bucket_sec'] ?? null, 3600),
        ];
    }

    // -------- response wrappers --------

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
