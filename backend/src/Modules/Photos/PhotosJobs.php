<?php
declare(strict_types=1);

namespace Backend\Modules\Photos;

/**
 * PhotosJobs
 *
 * Фоновые задачи для внешнего фото-сервиса.
 * Реально подключается через Adapters/QueueBus.
 */
final class PhotosJobs
{
    public function __construct(
        // TODO: инжект вашего QueueBus / PhotosAdapter
        // private QueueBus $bus,
        // private PhotosAdapter $adapter
    ) {}

    public function dispatchPhotosRun(int $taskId): void
    {
        // TODO: enqueue в очередь или вызов адаптера
        // $this->bus->push('photos.run', ['task_id' => $taskId]);
        // или: $this->adapter->run($taskId);
    }

    public function dispatchPhotosRetry(int $taskId, string $reason, bool $force): void
    {
        // TODO
        // $this->bus->push('photos.retry', [
        //     'task_id' => $taskId,
        //     'reason' => $reason,
        //     'force' => $force,
        // ]);
    }
}
