<?php

declare(strict_types=1);

namespace Cabinet\Backend\Domain\Shared\Exceptions;

final class InvalidStateTransition extends DomainException
{
    public static function forTransition(string $from, string $to, string $entity): self
    {
        return new self("Cannot transition {$entity} from {$from} to {$to}");
    }
}
