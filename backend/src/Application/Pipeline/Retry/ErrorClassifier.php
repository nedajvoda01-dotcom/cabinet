<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Retry;

use Backend\Application\Contracts\Error;
use Backend\Application\Contracts\ErrorKind;

final class ErrorClassifier
{
    public function classify(Error $error): ErrorKind
    {
        return $error->kind;
    }

    public function isRetryable(ErrorKind $kind): bool
    {
        return match ($kind) {
            ErrorKind::TRANSIENT, ErrorKind::RATE_LIMIT, ErrorKind::UNKNOWN => true,
            default => false,
        };
    }
}
