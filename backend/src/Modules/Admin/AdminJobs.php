<?php
declare(strict_types=1);

namespace Backend\Modules\Admin;

/**
 * AdminJobs
 *
 * В Spec Admin управляет retry/DLQ и очередями через jobs/workers.
 * Здесь — постановка задач в очередь (реальная очередь подключается в вашем DI).
 */
final class AdminJobs
{
    public function __construct(
        // TODO: инжект очереди/шины задач вашего проекта
        // private QueueBus $bus
    ) {}

    /**
     * Поставить retry для конкретного DLQ job.
     * Здесь мы не меняем БД — это делает AdminModel,
     * а job просто "пинает" воркер.
     */
    public function dispatchDlqRetry(int $jobId): void
    {
        // TODO: заменить на реальный enqueue
        // $this->bus->push('dlq.retry', ['job_id' => $jobId]);
    }

    /**
     * Поставить bulk retry.
     */
    public function dispatchDlqBulkRetry(array $jobIds): void
    {
        if (!$jobIds) return;

        // TODO: реальный enqueue пачки
        // $this->bus->push('dlq.bulk_retry', ['ids' => $jobIds]);
    }
}
