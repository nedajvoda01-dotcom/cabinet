<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Observability;

interface AuditLogger
{
    /**
     * Record an audit event
     */
    public function record(AuditEvent $event): void;
}
