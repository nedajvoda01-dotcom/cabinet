<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Bus;

use Cabinet\Backend\Application\Shared\Result;

/**
 * @template T
 */
interface CommandHandler
{
    /**
     * @return Result<T>
     */
    public function handle(Command $command): Result;
}
