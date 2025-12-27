<?php
declare(strict_types=1);

namespace Backend\Modules\Parser;

/**
 * ParserJobs
 *
 * Фоновые задачи парсинга.
 * Реальная интеграция с внешним парсером подключается в DI/Adapters.
 */
final class ParserJobs
{
    public function __construct(
        // TODO: инжект вашего QueueBus или Adapter клиента парсера
        // private QueueBus $bus,
        // private ParserAdapter $adapter
    ) {}

    public function dispatchParseRun(int $taskId): void
    {
        // TODO: заменить на реальный enqueue/вызов внешнего сервиса
        // $this->bus->push('parser.run', ['task_id' => $taskId]);
        // или: $this->adapter->run($taskId);
    }

    public function dispatchParseRetry(int $taskId, string $reason, bool $force): void
    {
        // TODO
        // $this->bus->push('parser.retry', [
        //     'task_id' => $taskId,
        //     'reason' => $reason,
        //     'force' => $force,
        // ]);
    }
}
