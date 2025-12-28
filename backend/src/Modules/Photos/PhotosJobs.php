<?php
declare(strict_types=1);

namespace Backend\Modules\Photos;

use App\Queues\QueueJob;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;

/**
 * PhotosJobs
 *
 * Фоновые задачи для внешнего фото-сервиса.
 * Реально подключается через Adapters/QueueBus.
 */
final class PhotosJobs
{
    public function __construct(private JobDispatcher $pipeline) {}

    public function dispatchPhotosRun(int $cardId, int $taskId, ?string $correlationId = null): QueueJob
    {
        return $this->pipeline->enqueue($this->createJob($cardId, [
            'task_id' => $taskId,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'photos.run',
        ]));
    }

    public function dispatchPhotosRetry(int $cardId, int $taskId, string $reason, bool $force, ?string $correlationId = null): QueueJob
    {
        return $this->pipeline->enqueue($this->createJob($cardId, [
            'task_id' => $taskId,
            'reason' => $reason,
            'force' => $force,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'photos.retry',
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createJob(int $cardId, array $payload): Job
    {
        return Job::create(JobType::PHOTOS, 'card', $cardId, $payload);
    }

    private function correlationId(?string $corr): string
    {
        return $corr ?: bin2hex(random_bytes(8));
    }
}
