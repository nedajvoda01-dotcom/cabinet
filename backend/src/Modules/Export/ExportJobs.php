<?php
declare(strict_types=1);

namespace Backend\Modules\Export;

use App\Queues\QueueJob;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;

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
    public function __construct(private JobDispatcher $pipeline) {}

    public function dispatchExportRun(int $exportId, ?string $correlationId = null): QueueJob
    {
        return $this->pipeline->enqueue($this->createJob($exportId, [
            'export_id' => $exportId,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'export.run',
        ]));
    }

    public function dispatchExportRetry(int $exportId, string $reason, bool $force, ?string $correlationId = null): QueueJob
    {
        return $this->pipeline->enqueue($this->createJob($exportId, [
            'export_id' => $exportId,
            'reason' => $reason,
            'force' => $force,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'export.retry',
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createJob(int $exportId, array $payload): Job
    {
        return Job::create(JobType::EXPORT, 'export', $exportId, $payload);
    }

    private function correlationId(?string $corr): string
    {
        return $corr ?: bin2hex(random_bytes(8));
    }
}
