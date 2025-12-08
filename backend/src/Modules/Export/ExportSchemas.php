<?php
declare(strict_types=1);

namespace Backend\Modules\Export;

use InvalidArgumentException;

/**
 * ExportSchemas
 *
 * DTO и валидация формы данных. Бизнес-правила — в ExportService.
 */
final class ExportSchemas
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

    private static function toStringOrNull(mixed $v): ?string
    {
        if ($v === null) return null;
        $s = trim((string)$v);
        return $s === '' ? null : $s;
    }

    // -------- Create export --------

    /**
     * POST /export
     * body:
     *  - type: "cards" | "photos" | "publish" | "custom" ...
     *  - params: object (фильтры/настройки экспорта)
     *  - format: "csv" | "xlsx" | "json" (optional)
     */
    public static function toCreateExportDto(array $body): array
    {
        self::validate($body, ['type'], [
            'type' => 'string',
            'params' => 'json',
            'format' => 'string',
        ]);

        $type = trim((string)$body['type']);
        if ($type === '') throw new InvalidArgumentException("Empty type");

        $format = isset($body['format']) ? trim((string)$body['format']) : 'csv';
        if ($format === '') $format = 'csv';

        return [
            'type' => $type,
            'params' => $body['params'] ?? [],
            'format' => $format,
        ];
    }

    // -------- List exports --------

    public static function toListExportsDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'type' => self::toStringOrNull($query['type'] ?? null),
            'status' => self::toStringOrNull($query['status'] ?? null),
            'user_id' => isset($query['user_id']) ? self::toInt($query['user_id']) : null,
            'from_ts' => isset($query['from_ts']) ? self::toInt($query['from_ts']) : null,
            'to_ts' => isset($query['to_ts']) ? self::toInt($query['to_ts']) : null,
        ];
    }

    // -------- Id / actions --------

    public static function toExportIdDto(string|int $id): array
    {
        $idInt = self::toInt($id);
        if ($idInt <= 0) throw new InvalidArgumentException("Invalid export id");
        return ['id' => $idInt];
    }

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
