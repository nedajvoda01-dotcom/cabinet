<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

interface UnitOfWork
{
    public function commit(): void;

    public function rollback(): void;
}
