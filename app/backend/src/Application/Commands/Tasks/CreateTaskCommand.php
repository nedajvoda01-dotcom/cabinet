<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Commands\Tasks;

use Cabinet\Backend\Application\Bus\Command;

final class CreateTaskCommand implements Command
{
    public function __construct(
        private readonly string $actorId,
        private readonly string $idempotencyKey
    ) {
    }

    public function actorId(): string
    {
        return $this->actorId;
    }

    public function idempotencyKey(): string
    {
        return $this->idempotencyKey;
    }
}
