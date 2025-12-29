<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Commands\Access;

use Cabinet\Backend\Application\Bus\Command;

final class RequestAccessCommand implements Command
{
    public function __construct(
        private readonly string $requestedBy
    ) {
    }

    public function requestedBy(): string
    {
        return $this->requestedBy;
    }
}
