<?php
declare(strict_types=1);

namespace Backend\Modules\Parser;

use Backend\Application\Contracts\TraceContext;
use InvalidArgumentException;

/**
 * ParserSchemas
 *
 * DTO + валидация формы данных.
 * Бизнес-валидация и SM-переходы — в ParserService.
 */
final class ParserSchemas
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

    // -------- Create / run parsing --------

    /**
     * POST /parser/run
     * body:
     *  - card_id (required)
     *  - source_url (optional, если ручной парсинг)
     *  - force (optional)
     *  - params (optional arbitrary json)
     */
    public static function toRunParserDto(array $body): array
    {
        self::validate($body, ['card_id'], [
            'card_id' => 'int',
            'source_url' => 'string',
            'force' => 'bool',
            'params' => 'json',
        ]);

        $cardId = self::toInt($body['card_id']);
        if ($cardId <= 0) throw new InvalidArgumentException("Invalid card_id");

        $sourceUrl = self::toStringOrNull($body['source_url'] ?? null);

        return [
            'card_id' => $cardId,
            'source_url' => $sourceUrl,
            'force' => self::toBool($body['force'] ?? null, false),
            'params' => $body['params'] ?? [],
        ];
    }

    // -------- List tasks --------

    public static function toListParserTasksDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'status' => self::toStringOrNull($query['status'] ?? null),
            'type' => self::toStringOrNull($query['type'] ?? null),
            'card_id' => isset($query['card_id']) ? self::toInt($query['card_id']) : null,
            'from_ts' => isset($query['from_ts']) ? self::toInt($query['from_ts']) : null,
            'to_ts' => isset($query['to_ts']) ? self::toInt($query['to_ts']) : null,
        ];
    }

    // -------- Id / retry --------

    public static function toTaskIdDto(string|int $id): array
    {
        $idInt = self::toInt($id);
        if ($idInt <= 0) throw new InvalidArgumentException("Invalid task id");
        return ['id' => $idInt];
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

    // -------- Webhook result DTO --------

    /**
     * POST /parser/webhook
     * body:
     *  - task_id (required)
     *  - card_id (required)
     *  - status: done|failed (required)
     *  - parsed_payload (optional json)
     *  - error_code / error_message (optional)
     */
    public static function toWebhookDto(array $body): array
    {
        self::validate($body, ['task_id', 'card_id', 'status'], [
            'task_id' => 'int',
            'card_id' => 'int',
            'status' => 'string',
            'parsed_payload' => 'json',
            'error_code' => 'string',
            'error_message' => 'string',
        ]);

        $taskId = self::toInt($body['task_id']);
        $cardId = self::toInt($body['card_id']);
        $status = trim((string)$body['status']);

        if ($taskId <= 0) throw new InvalidArgumentException("Invalid task_id");
        if ($cardId <= 0) throw new InvalidArgumentException("Invalid card_id");
        if (!in_array($status, ['done', 'failed'], true)) {
            throw new InvalidArgumentException("Invalid status");
        }

        return [
            'task_id' => $taskId,
            'card_id' => $cardId,
            'status' => $status,
            'parsed_payload' => $body['parsed_payload'] ?? null,
            'error_code' => self::toStringOrNull($body['error_code'] ?? null),
            'error_message' => self::toStringOrNull($body['error_message'] ?? null),
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
        return ['ok' => false, 'error' => $err, 'traceId' => TraceContext::ensure()->traceId()];
    }
}
