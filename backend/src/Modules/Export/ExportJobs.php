<?php
declare(strict_types=1);

namespace Backend\Modules\Export;

use App\Queues\QueueJob;
use App\Queues\QueueService;

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
    public function __construct(private QueueService $queues) {}

    public function dispatchExportRun(int $exportId, ?string $correlationId = null): QueueJob
    {
        return $this->queues->enqueueExport($exportId, [
            'export_id' => $exportId,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'export.run',
        ]);
    }

    public function dispatchExportRetry(int $exportId, string $reason, bool $force, ?string $correlationId = null): QueueJob
    {
        return $this->queues->enqueueExport($exportId, [
            'export_id' => $exportId,
            'reason' => $reason,
            'force' => $force,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'export.retry',
        ]);
    }

    private function correlationId(?string $corr): string
    {
        return $corr ?: bin2hex(random_bytes(8));
    }
}
