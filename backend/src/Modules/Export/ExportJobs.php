<?php
declare(strict_types=1);

namespace Backend\Modules\Export;

/**
 * ExportJobs
 *
 * Фоновая работа:
 *  - запуск экспорта
 *  - retry экспорта
 *
 * Реальную очередь подключаете в DI.
 */
final class ExportJobs
{
    public function __construct(
        // TODO: инжект вашего QueueBus/Dispatcher
        // private QueueBus $bus
    ) {}

    public function dispatchExportRun(int $exportId): void
    {
        // TODO: заменить на реальную очередь
        // $this->bus->push('export.run', ['export_id' => $exportId]);
    }

    public function dispatchExportRetry(int $exportId, string $reason, bool $force): void
    {
        // TODO
        // $this->bus->push('export.retry', [
        //     'export_id' => $exportId,
        //     'reason' => $reason,
        //     'force' => $force,
        // ]);
    }
}
