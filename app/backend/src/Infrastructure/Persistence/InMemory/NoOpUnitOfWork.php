<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\InMemory;

use Cabinet\Backend\Application\Ports\UnitOfWork;

final class NoOpUnitOfWork implements UnitOfWork
{
    public function commit(): void
    {
        // No-op for in-memory implementation
    }

    public function rollback(): void
    {
        // No-op for in-memory implementation
    }
}
