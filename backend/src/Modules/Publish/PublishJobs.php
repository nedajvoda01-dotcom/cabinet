<?php
declare(strict_types=1);

namespace Backend\Modules\Publish;

use App\Queues\QueueJob;
use App\Queues\QueueService;

/**
 * PublishJobs
 *
 * Фоновые задачи публикации.
 * Реальная интеграция с площадками (Avito/Dolphin и т.п.) — через Adapters/QueueBus.
 */
final class PublishJobs
{
    public function __construct(private QueueService $queues) {}

    public function dispatchPublishRun(int $cardId, int $taskId, ?string $correlationId = null): QueueJob
    {
        return $this->queues->enqueuePublish($cardId, [
            'task_id' => $taskId,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'publish.run',
        ]);
    }

    public function dispatchPublishRetry(int $cardId, int $taskId, string $reason, bool $force, ?string $correlationId = null): QueueJob
    {
        return $this->queues->enqueuePublish($cardId, [
            'task_id' => $taskId,
            'reason' => $reason,
            'force' => $force,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'publish.retry',
        ]);
    }

    public function dispatchPublishCancel(int $cardId, int $taskId, string $reason, ?string $correlationId = null): QueueJob
    {
        return $this->queues->enqueuePublish($cardId, [
            'task_id' => $taskId,
            'reason' => $reason,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'publish.cancel',
        ]);
    }

    private function correlationId(?string $corr): string
    {
        return $corr ?: bin2hex(random_bytes(8));
    }
}
