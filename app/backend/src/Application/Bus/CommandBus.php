<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Bus;

use Cabinet\Backend\Application\Shared\Result;

final class CommandBus
{
    /** @var array<string, CommandHandler<mixed>> */
    private array $handlers = [];

    /**
     * @template T
     * @param CommandHandler<T> $handler
     */
    public function register(string $commandClass, CommandHandler $handler): void
    {
        $this->handlers[$commandClass] = $handler;
    }

    /**
     * @template T
     * @return Result<T>
     */
    public function dispatch(Command $command): Result
    {
        $commandClass = get_class($command);

        if (!isset($this->handlers[$commandClass])) {
            throw new \LogicException(sprintf('No handler registered for command: %s', $commandClass));
        }

        return $this->handlers[$commandClass]->handle($command);
    }
}
