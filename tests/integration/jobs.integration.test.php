<?php
// cabinet/tests/integration/jobs.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Queues\QueueService;
use App\Queues\RetryPolicy;
use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use Backend\Modules\Parser\ParserJobs;
use Backend\Modules\Photos\PhotosJobs;
use Backend\Modules\Export\ExportJobs;
use Backend\Modules\Publish\PublishJobs;

final class JobsIntegrationTest extends TestCase
{
    private QueueService $queues;
    private FakeQueueRepo $repo;
    private FakeDlqRepo $dlq;

    protected function setUp(): void
    {
        $this->repo = new FakeQueueRepo();
        $this->dlq = new FakeDlqRepo();
        $this->queues = new QueueService($this->repo, $this->dlq, new RetryPolicy(), new NullLogger());
    }

    public function testParserJobEnqueuedAndFailureHandled(): void
    {
        $jobs = new ParserJobs($this->queues);
        $job = $jobs->dispatchParseRun(1, 'cid-parser-int');

        $this->assertSame(QueueTypes::PARSER, $job->type);
        $this->assertSame('cid-parser-int', $this->repo->jobs[$job->id]->payload['correlation_id']);

        $this->queues->handleFailure($job, ['message' => 'boom']);
        $stored = $this->repo->jobs[$job->id];
        $this->assertSame('retrying', $stored->status);
    }

    public function testPhotosRetryToDlqOnFatal(): void
    {
        $jobs = new PhotosJobs($this->queues);
        $job = $jobs->dispatchPhotosRetry(2, 3, 'oops', true, 'cid-photos-int');

        $this->assertSame(QueueTypes::PHOTOS, $job->type);
        $this->queues->handleFailure($job, ['message' => 'fatal', 'fatal' => true]);

        $stored = $this->repo->jobs[$job->id];
        $this->assertSame('dead', $stored->status);
        $this->assertCount(1, $this->dlq->jobs);
    }

    public function testExportJobPayload(): void
    {
        $jobs = new ExportJobs($this->queues);
        $job = $jobs->dispatchExportRun(4, 'cid-export-int');

        $this->assertSame(QueueTypes::EXPORT, $job->type);
        $this->assertSame(4, $job->entityId);
        $this->assertSame('cid-export-int', $job->payload['correlation_id']);
    }

    public function testPublishCancelPayload(): void
    {
        $jobs = new PublishJobs($this->queues);
        $job = $jobs->dispatchPublishCancel(10, 11, 'user_cancel', 'cid-publish-int');

        $this->assertSame(QueueTypes::PUBLISH, $job->type);
        $this->assertSame(10, $job->entityId);
        $this->assertSame('user_cancel', $job->payload['reason']);
    }
}

final class FakeQueueRepo
{
    /** @var array<int,QueueJob> */
    public array $jobs = [];
    private int $seq = 1;

    public function enqueue(string $type, string $entity, int $entityId, array $payload): QueueJob
    {
        $job = new QueueJob();
        $job->id = $this->seq++;
        $job->type = $type;
        $job->entity = $entity;
        $job->entityId = $entityId;
        $job->payload = $payload;
        $job->status = 'queued';
        $this->jobs[$job->id] = $job;
        return $job;
    }

    public function fetchNext(string $type, string $workerId): ?QueueJob { return null; }
    public function markDone(int $id): void { $this->jobs[$id]->status = 'done'; }

    public function markRetrying(int $id, int $attempts, \DateTimeImmutable|string $nextRetryAt, array $error): void
    {
        $j = $this->jobs[$id];
        $j->status = 'retrying';
        $j->attempts = $attempts;
        $j->nextRetryAt = is_string($nextRetryAt) ? $nextRetryAt : $nextRetryAt->format('c');
        $j->lastError = $error;
    }

    public function markDead(int $id, int $attempts, array $error): void
    {
        $j = $this->jobs[$id];
        $j->status = 'dead';
        $j->attempts = $attempts;
        $j->lastError = $error;
    }
}

final class FakeDlqRepo
{
    /** @var QueueJob[] */
    public array $jobs = [];
    public function put(QueueJob $job): void { $this->jobs[] = $job; }
}

final class NullLogger implements \Backend\Logger\LoggerInterface
{
    public function info(string $m, array $c = []): void {}
    public function warn(string $m, array $c = []): void {}
    public function error(string $m, array $c = []): void {}
    public function audit(string $t, string $m, array $c = []): void {}
}
