<?php
// backend/src/Logger/DbLogger.php

namespace Backend\Logger;

use PDO;

final class DbLogger implements LoggerInterface
{
    public function __construct(private PDO $db) {}

    public function info(string $message, array $context = []): void
    {
        $this->write('info', null, $message, $context);
    }

    public function warn(string $message, array $context = []): void
    {
        $this->write('warn', null, $message, $context);
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('error', null, $message, $context);
    }

    public function audit(string $type, string $message, array $context = []): void
    {
        $this->write('info', $type, $message, $context);
    }

    private function write(string $level, ?string $type, string $message, array $context): void
    {
        $ctx = $context;
        $correlationId = $ctx['correlation_id'] ?? LogContext::correlationId($ctx);
        $cardId = $ctx['card_id'] ?? null;

        unset($ctx['correlation_id'], $ctx['card_id']);

        $st = $this->db->prepare("
            INSERT INTO system_logs(level, type, message, context_json, correlation_id, card_id)
            VALUES(:level, :type, :message, :context_json::jsonb, :correlation_id, :card_id)
        ");

        $st->execute([
            ':level' => $level,
            ':type' => $type,
            ':message' => $message,
            ':context_json' => json_encode($ctx, JSON_UNESCAPED_UNICODE),
            ':correlation_id' => $correlationId,
            ':card_id' => $cardId ? (int)$cardId : null,
        ]);
    }
}
