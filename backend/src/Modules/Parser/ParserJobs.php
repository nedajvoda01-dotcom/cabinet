<?php
declare(strict_types=1);

namespace Backend\Modules\Parser;

use App\Queues\QueueJob;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;

/**
 * ParserJobs
 *
 * Фоновые задачи парсинга.
 * Реальная интеграция с внешним парсером подключается в DI/Adapters.
 */
final class ParserJobs
{
    public function __construct(private JobDispatcher $pipeline) {}

    public function dispatchParseRun(int $taskId, ?string $correlationId = null): QueueJob
    {
        return $this->pipeline->enqueue($this->createJob($taskId, [
            'task_id' => $taskId,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'parse.run',
        ]));
    }

    public function dispatchParseRetry(int $taskId, string $reason, bool $force, ?string $correlationId = null): QueueJob
    {
        return $this->pipeline->enqueue($this->createJob($taskId, [
            'task_id' => $taskId,
            'reason' => $reason,
            'force' => $force,
            'correlation_id' => $this->correlationId($correlationId),
            'action' => 'parse.retry',
        ]));
    }

    /**
     * @param array<string, mixed> $payload
     */
    private function createJob(int $taskId, array $payload): Job
    {
        return Job::create(JobType::PARSER, 'parser_payload', $taskId, $payload);
    }

    private function correlationId(?string $corr): string
    {
        return $corr ?: bin2hex(random_bytes(8));
    }
}
