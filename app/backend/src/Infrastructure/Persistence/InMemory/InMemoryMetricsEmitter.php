<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\InMemory;

use Cabinet\Backend\Application\Observability\MetricsEmitter;

final class InMemoryMetricsEmitter implements MetricsEmitter
{
    /** @var array<array{name: string, tags: array<string, string>}> */
    private array $metrics = [];

    /**
     * @param array<string, string> $tags
     */
    public function increment(string $name, array $tags = []): void
    {
        $this->metrics[] = ['name' => $name, 'tags' => $tags];
    }

    /**
     * @return array<array{name: string, tags: array<string, string>}>
     */
    public function getMetrics(): array
    {
        return $this->metrics;
    }

    public function clear(): void
    {
        $this->metrics = [];
    }

    /**
     * Check if a metric with given name exists
     */
    public function hasMetric(string $name): bool
    {
        foreach ($this->metrics as $metric) {
            if ($metric['name'] === $name) {
                return true;
            }
        }
        return false;
    }
}
