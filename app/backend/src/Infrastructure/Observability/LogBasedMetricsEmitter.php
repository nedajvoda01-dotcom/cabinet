<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Observability;

use Cabinet\Backend\Application\Observability\MetricsEmitter;
use Cabinet\Backend\Infrastructure\Observability\Logging\StructuredLogger;

final class LogBasedMetricsEmitter implements MetricsEmitter
{
    private StructuredLogger $logger;

    public function __construct(StructuredLogger $logger)
    {
        $this->logger = $logger;
    }

    /**
     * @param array<string, string> $tags
     */
    public function increment(string $name, array $tags = []): void
    {
        $context = array_merge(['metric' => $name], $tags);
        $this->logger->info('metric.increment', $context);
    }
}
