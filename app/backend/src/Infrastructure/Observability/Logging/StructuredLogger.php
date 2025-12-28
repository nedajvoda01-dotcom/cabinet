<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Observability\Logging;

use Cabinet\Backend\Bootstrap\Clock;

final class StructuredLogger
{
    private Clock $clock;
    private $stream;

    public function __construct(Clock $clock)
    {
        $this->clock = $clock;
        // Open stdout stream once and reuse
        $this->stream = defined('STDOUT') ? STDOUT : fopen('php://stdout', 'w');
    }

    /**
     * @param array<string, mixed> $context
     */
    public function info(string $event, array $context = []): void
    {
        $this->log('info', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function error(string $event, array $context = []): void
    {
        $this->log('error', $event, $context);
    }

    /**
     * @param array<string, mixed> $context
     */
    private function log(string $level, string $event, array $context): void
    {
        $payload = array_merge([
            'timestamp' => $this->clock->now()->format(DATE_ATOM),
            'level' => $level,
            'event' => $event,
        ], $context);

        if ($this->stream) {
            fwrite($this->stream, (string) json_encode($payload, JSON_UNESCAPED_SLASHES) . PHP_EOL);
        }
    }
}
