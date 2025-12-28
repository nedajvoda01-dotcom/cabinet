<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Idempotency;

use Backend\Application\Pipeline\Jobs\Job;

final class InMemoryIdempotencyStore implements IdempotencyStoreInterface
{
    /** @var array<string, string> */
    private array $states = [];

    public function acquire(Job $job): bool
    {
        $key = $job->idempotencyKey();
        $state = $this->states[$key] ?? null;
        if ($state === 'committed' || $state === 'inflight') {
            return false;
        }

        $this->states[$key] = 'inflight';

        return true;
    }

    public function commit(Job $job): void
    {
        $this->states[$job->idempotencyKey()] = 'committed';
    }

    public function release(Job $job): void
    {
        unset($this->states[$job->idempotencyKey()]);
    }
}
