<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

interface IdGenerator
{
    public function generate(): string;
}
