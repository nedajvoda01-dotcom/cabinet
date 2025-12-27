<?php
declare(strict_types=1);

namespace Backend\Modules\Publish;

/**
 * PublishJobs
 *
 * Фоновые задачи публикации.
 * Реальная интеграция с площадками (Avito/Dolphin и т.п.) — через Adapters/QueueBus.
 */
final class PublishJobs
{
    public function __construct(
        // TODO: инжект QueueBus / PublishAdapter
        // private QueueBus $bus,
        // private PublishAdapter $adapter
    ) {}

    public function dispatchPublishRun(int $taskId): void
    {
        // TODO: enqueue или вызов адаптера
        // $this->bus->push('publish.run', ['task_id' => $taskId]);
    }

    public function dispatchPublishRetry(int $taskId, string $reason, bool $force): void
    {
        // TODO
        // $this->bus->push('publish.retry', [
        //     'task_id' => $taskId,
        //     'reason' => $reason,
        //     'force' => $force,
        // ]);
    }

    public function dispatchPublishCancel(int $taskId, string $reason): void
    {
        // TODO optional: уведомить внешний сервис
        // $this->bus->push('publish.cancel', [
        //     'task_id' => $taskId,
        //     'reason' => $reason,
        // ]);
    }
}
