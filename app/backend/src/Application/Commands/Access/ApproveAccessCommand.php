<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Commands\Access;

use Cabinet\Backend\Application\Bus\Command;

final class ApproveAccessCommand implements Command
{
    public function __construct(
        private readonly string $accessRequestId,
        private readonly string $resolverUserId
    ) {
    }

    public function accessRequestId(): string
    {
        return $this->accessRequestId;
    }

    public function resolverUserId(): string
    {
        return $this->resolverUserId;
    }
}
