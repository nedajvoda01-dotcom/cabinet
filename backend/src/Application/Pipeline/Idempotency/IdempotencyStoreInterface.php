<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Idempotency;

use Backend\Application\Pipeline\Jobs\Job;

interface IdempotencyStoreInterface
{
    public function acquire(Job $job): bool;

    public function commit(Job $job): void;

    public function release(Job $job): void;
}
