<?php

declare(strict_types=1);

namespace Cabinet\Backend\Bootstrap;

use DateTimeImmutable;

final class Clock
{
    public function now(): DateTimeImmutable
    {
        return new DateTimeImmutable();
    }
}
