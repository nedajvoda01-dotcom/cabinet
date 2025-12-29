<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

use Cabinet\Backend\Domain\Users\AccessRequest;
use Cabinet\Backend\Domain\Users\AccessRequestId;

interface AccessRequestRepository
{
    public function save(AccessRequest $accessRequest): void;

    public function findById(AccessRequestId $id): ?AccessRequest;
}
