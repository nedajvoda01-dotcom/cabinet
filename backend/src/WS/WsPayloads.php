<?php
// backend/src/WS/WsPayloads.php

namespace App\WS;

final class WsPayloads
{
    public static function cardStatusUpdated(int $cardId, string $stage, string $status, array $meta = []): array
    {
        return [
            'card_id' => $cardId,
            'stage' => $stage,
            'status' => $status,
            'meta' => $meta,
        ];
    }

    public static function progress(string $kind, array $fields): array
    {
        return array_merge(['kind' => $kind], $fields);
    }

    public static function queueDepthUpdated(string $type, int $depth, bool $paused = false, array $meta = []): array
    {
        return [
            'type' => $type,
            'depth' => $depth,
            'paused' => $paused,
            'meta' => $meta,
        ];
    }

    public static function dlqUpdated(int $count, array $meta = []): array
    {
        return [
            'count' => $count,
            'meta' => $meta,
        ];
    }

    public static function healthUpdated(bool $ok, array $integrations, array $kpi = []): array
    {
        return [
            'ok' => $ok,
            'integrations' => $integrations,
            'kpi' => $kpi,
        ];
    }
}
