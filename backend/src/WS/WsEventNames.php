<?php
// backend/src/WS/WsEventNames.php

namespace App\WS;

/**
 * Единый список WS событий по Spec.
 * Можно расширять, но имена должны совпадать с frontend/shared/ws/events.ts
 */
final class WsEventNames
{
    public const CARD_STATUS_UPDATED   = 'card.status.updated';
    public const PHOTOS_PROGRESS       = 'photos.progress';
    public const EXPORT_PROGRESS       = 'export.progress';
    public const PUBLISH_PROGRESS      = 'publish.progress';
    public const PUBLISH_STATUS_UPDATED= 'publish.status.updated';

    public const QUEUE_DEPTH_UPDATED   = 'queue.depth.updated';
    public const DLQ_UPDATED           = 'dlq.updated';
    public const HEALTH_UPDATED        = 'health.updated';

    public static function all(): array
    {
        return [
            self::CARD_STATUS_UPDATED,
            self::PHOTOS_PROGRESS,
            self::EXPORT_PROGRESS,
            self::PUBLISH_PROGRESS,
            self::PUBLISH_STATUS_UPDATED,
            self::QUEUE_DEPTH_UPDATED,
            self::DLQ_UPDATED,
            self::HEALTH_UPDATED,
        ];
    }
}
