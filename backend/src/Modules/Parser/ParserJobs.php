<?php
declare(strict_types=1);

namespace Backend\Modules\Parser;

use App\Queues\QueueJob;
use App\Queues\QueueService;

/**
 * ParserJobs
 *
 * Фоновые задачи парсинга.
 * Реальная интеграция с внешним парсером подключается в DI/Adapters.
 */
final class ParserJobs
{
    public function __construct(private QueueService $queues) {}

    public function dispatchParseRun(int $taskId, ?string $correlationId = null): QueueJob
    {
        return $this->queues->enqueueParser($taskId, [
            'task_id' => $taskId,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'parse.run',
        ]);
    }

    public function dispatchParseRetry(int $taskId, string $reason, bool $force, ?string $correlationId = null): QueueJob
    {
        return $this->queues->enqueueParser($taskId, [
            'task_id' => $taskId,
            'reason' => $reason,
            'force' => $force,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'parse.retry',
        ]);
    }

    private function correlationId(?string $corr): string
    {
        return $corr ?: bin2hex(random_bytes(8));
    }
}
