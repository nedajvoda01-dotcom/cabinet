<?php
declare(strict_types=1);

namespace Backend\Modules\Photos;

use Backend\Application\Contracts\TraceContext;
use InvalidArgumentException;

/**
 * PhotosSchemas
 *
 * DTO + валидация формы данных.
 * Бизнес-валидация / SM-переходы — в PhotosService.
 */
final class PhotosSchemas
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

    // -------- Run photos pipeline --------

    /**
     * POST /photos/run
     * body:
     *  - card_id (required)
     *  - mode: "generate"|"process"|"upload" (optional, default generate)
     *  - source_urls: array<string> (optional, если mode=process/upload)
     *  - force (optional)
     *  - params (optional json)
     */
    public static function toRunPhotosDto(array $body): array
    {
        self::validate($body, ['card_id'], [
            'card_id' => 'int',
            'mode' => 'string',
            'source_urls' => 'array',
            'force' => 'bool',
            'params' => 'json',
        ]);

        $cardId = self::toInt($body['card_id']);
        if ($cardId <= 0) throw new InvalidArgumentException("Invalid card_id");

        $mode = isset($body['mode']) ? trim((string)$body['mode']) : 'generate';
        if ($mode === '') $mode = 'generate';
        if (!in_array($mode, ['generate','process','upload'], true)) {
            throw new InvalidArgumentException("Invalid mode");
        }

        $urls = [];
        if (!empty($body['source_urls'])) {
            foreach ($body['source_urls'] as $u) {
                if (!is_string($u) || trim($u) === '') {
                    throw new InvalidArgumentException("Invalid source_urls");
                }
                $urls[] = trim($u);
            }
        }

        return [
            'card_id' => $cardId,
            'mode' => $mode,
            'source_urls' => $urls,
            'force' => self::toBool($body['force'] ?? null, false),
            'params' => $body['params'] ?? [],
        ];
    }

    // -------- List photo tasks --------

    public static function toListPhotoTasksDto(array $query): array
    {
        return [
            'limit' => self::toInt($query['limit'] ?? null, 50),
            'offset' => self::toInt($query['offset'] ?? null, 0),
            'status' => self::toStringOrNull($query['status'] ?? null),
            'mode' => self::toStringOrNull($query['mode'] ?? null),
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

    // -------- Photos (artifacts) --------

    public static function toListCardPhotosDto(array $query): array
    {
        return [
            'card_id' => self::toInt($query['card_id'] ?? null, 0),
        ];
    }

    public static function toPhotoIdDto(string|int $id): array
    {
        $idInt = self::toInt($id);
        if ($idInt <= 0) throw new InvalidArgumentException("Invalid photo id");
        return ['id' => $idInt];
    }

    public static function toSetPrimaryDto(array $body): array
    {
        self::validate($body, ['photo_id'], ['photo_id' => 'int']);
        return ['photo_id' => self::toPhotoIdDto($body['photo_id'])['id']];
    }

    // -------- Webhook --------

    /**
     * POST /photos/webhook
     * body:
     *  - task_id (required)
     *  - card_id (required)
     *  - status: done|failed (required)
     *  - photos: array<object> (optional on done)
     *      [{ url, width?, height?, kind?, meta? }]
     *  - error_code / error_message (optional on failed)
     */
    public static function toWebhookDto(array $body): array
    {
        self::validate($body, ['task_id', 'card_id', 'status'], [
            'task_id' => 'int',
            'card_id' => 'int',
            'status' => 'string',
            'photos' => 'array',
            'error_code' => 'string',
            'error_message' => 'string',
        ]);

        $taskId = self::toInt($body['task_id']);
        $cardId = self::toInt($body['card_id']);
        $status = trim((string)$body['status']);

        if ($taskId <= 0) throw new InvalidArgumentException("Invalid task_id");
        if ($cardId <= 0) throw new InvalidArgumentException("Invalid card_id");
        if (!in_array($status, ['done','failed'], true)) {
            throw new InvalidArgumentException("Invalid status");
        }

        $photos = [];
        if ($status === 'done' && !empty($body['photos'])) {
            foreach ($body['photos'] as $p) {
                if (!is_array($p)) continue;
                if (empty($p['url']) || !is_string($p['url'])) {
                    throw new InvalidArgumentException("Photo url required");
                }
                $photos[] = [
                    'url' => trim((string)$p['url']),
                    'width' => isset($p['width']) ? self::toInt($p['width']) : null,
                    'height' => isset($p['height']) ? self::toInt($p['height']) : null,
                    'kind' => isset($p['kind']) ? trim((string)$p['kind']) : null,
                    'meta' => is_array($p['meta'] ?? null) ? $p['meta'] : [],
                ];
            }
        }

        return [
            'task_id' => $taskId,
            'card_id' => $cardId,
            'status' => $status,
            'photos' => $photos,
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
