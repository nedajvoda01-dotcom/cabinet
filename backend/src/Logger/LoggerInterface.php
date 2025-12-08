<?php
// backend/src/Logger/LoggerInterface.php

namespace Backend\Logger;

interface LoggerInterface
{
    public function info(string $message, array $context = []): void;
    public function warn(string $message, array $context = []): void;
    public function error(string $message, array $context = []): void;

    /**
     * Аудит админ / ручных действий.
     * type: например 'dlq.retry', 'card.force_parse'
     */
    public function audit(string $type, string $message, array $context = []): void;
}
