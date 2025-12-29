<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Observability;

interface MetricsEmitter
{
    /**
     * Increment a counter metric
     * 
     * @param array<string, string> $tags
     */
    public function increment(string $name, array $tags = []): void;
}
