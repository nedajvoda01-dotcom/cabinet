<?php
// backend/src/Logger/LogContext.php

namespace Backend\Logger;

final class LogContext
{
    /**
     * Стабильно получить correlation_id:
     * - из payload job
     * - из request context
     * - или сгенерить новый
     */
    public static function correlationId(array $ctx = []): string
    {
        if (!empty($ctx['correlation_id'])) return (string)$ctx['correlation_id'];
        if (!empty($ctx['job']['correlation_id'])) return (string)$ctx['job']['correlation_id'];
        if (!empty($ctx['req']['correlation_id'])) return (string)$ctx['req']['correlation_id'];

        return self::newId();
    }

    public static function newId(): string
    {
        return bin2hex(random_bytes(8)) . '-' . time();
    }

    public static function withCorrelation(array $context, ?string $correlationId): array
    {
        if ($correlationId) {
            $context['correlation_id'] = $correlationId;
        } elseif (empty($context['correlation_id'])) {
            $context['correlation_id'] = self::newId();
        }
        return $context;
    }
}
