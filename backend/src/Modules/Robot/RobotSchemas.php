<?php
declare(strict_types=1);

namespace Modules\Robot;

final class RobotSchemas
{
    public const RUN_STATUSES = [
        'queued',
        'processing',
        'external_wait',
        'success',
        'failed_retry',
        'failed_fatal',
    ];

    /**
     * Payload that PublishModule sends to Robot.
     */
    public static function validatePublishPayload(array $payload): array
    {
        $required = ['card_id', 'publish_job_id', 'card_snapshot'];
        foreach ($required as $k) {
            if (!array_key_exists($k, $payload)) {
                throw new \InvalidArgumentException("Robot publish payload missing field: {$k}");
            }
        }

        $payload['card_id'] = (int)$payload['card_id'];
        $payload['publish_job_id'] = (int)$payload['publish_job_id'];

        if ($payload['card_id'] <= 0 || $payload['publish_job_id'] <= 0) {
            throw new \InvalidArgumentException("Robot publish payload has invalid ids");
        }

        if (!is_array($payload['card_snapshot'])) {
            throw new \InvalidArgumentException("Robot publish payload card_snapshot must be an array");
        }

        $payload['options'] = isset($payload['options']) && is_array($payload['options'])
            ? $payload['options']
            : [];

        return $payload;
    }

    /**
     * External status sync payload.
     */
    public static function validateSyncPayload(array $payload): array
    {
        $payload['filter'] = isset($payload['filter']) && is_array($payload['filter'])
            ? $payload['filter']
            : [];
        return $payload;
    }

    public static function assertStatus(string $status): void
    {
        if (!in_array($status, self::RUN_STATUSES, true)) {
            throw new \InvalidArgumentException("Unknown RobotRun status: {$status}");
        }
    }
}
