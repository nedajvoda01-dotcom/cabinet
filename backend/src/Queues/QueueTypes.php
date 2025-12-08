<?php
// backend/src/Queues/QueueTypes.php

namespace App\Queues;

/**
 * Фиксированный список типов очередей по Spec.
 * Можно расширять - но имена должны совпадать с UI/WS/Workers.
 */
final class QueueTypes
{
    public const PHOTOS       = 'photos';
    public const EXPORT       = 'export';
    public const PUBLISH      = 'publish';
    public const PARSER       = 'parser';
    public const ROBOT_STATUS = 'robot_status';

    public static function all(): array
    {
        return [
            self::PHOTOS,
            self::EXPORT,
            self::PUBLISH,
            self::PARSER,
            self::ROBOT_STATUS,
        ];
    }

    public static function assertValid(string $type): void
    {
        if (!in_array($type, self::all(), true)) {
            throw new QueueException("Unknown queue type: {$type}");
        }
    }
}
