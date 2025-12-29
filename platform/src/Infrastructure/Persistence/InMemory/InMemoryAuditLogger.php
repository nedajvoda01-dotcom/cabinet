<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\InMemory;

use Cabinet\Backend\Application\Observability\AuditEvent;
use Cabinet\Backend\Application\Observability\AuditLogger;

final class InMemoryAuditLogger implements AuditLogger
{
    /** @var array<AuditEvent> */
    private array $events = [];

    public function record(AuditEvent $event): void
    {
        $this->events[] = $event;
    }

    /**
     * @return array<AuditEvent>
     */
    public function getEvents(): array
    {
        return $this->events;
    }

    public function clear(): void
    {
        $this->events = [];
    }
}
