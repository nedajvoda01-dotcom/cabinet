<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\InMemory;

use Cabinet\Backend\Application\Ports\AccessRequestRepository;
use Cabinet\Backend\Domain\Users\AccessRequest;
use Cabinet\Backend\Domain\Users\AccessRequestId;

final class InMemoryAccessRequestRepository implements AccessRequestRepository
{
    /** @var array<string, AccessRequest> */
    private array $requests = [];

    public function save(AccessRequest $accessRequest): void
    {
        $this->requests[$accessRequest->id()->toString()] = $accessRequest;
    }

    public function findById(AccessRequestId $id): ?AccessRequest
    {
        return $this->requests[$id->toString()] ?? null;
    }
}
