<?php
// tests/unit/backend/queue.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Queues\QueueService;
use App\Queues\QueueTypes;
use App\Queues\QueueJob;
use App\Queues\RetryPolicy;
use Backend\Logger\LoggerInterface;

final class QueueServiceTest extends TestCase
{
    private QueueService $svc;
    private FakeQueueRepo $repo;
    private FakeDlqRepo $dlq;
    private RetryPolicy $policy;

    protected function setUp(): void
    {
        $this->repo = new FakeQueueRepo();
        $this->dlq = new FakeDlqRepo();
        $this->policy = new RetryPolicy();

        $log = new class implements LoggerInterface {
            public array $events = [];
            public function info(string $m, array $c = []): void { $this->events[]=['level'=>'info','m'=>$m,'c'=>$c];}
            public function warn(string $m, array $c = []): void { $this->events[]=['level'=>'warn','m'=>$m,'c'=>$c];}
            public function error(string $m, array $c = []): void { $this->events[]=['level'=>'error','m'=>$m,'c'=>$c];}
            public function audit(string $t, string $m, array $c = []): void { $this->events[]=['level'=>'audit','t'=>$t,'m'=>$m,'c'=>$c];}
        };

        $this->svc = new QueueService($this->repo, $this->dlq, $this->policy, $log);
    }

    public function testEnqueueCreatesJob(): void
    {
        $job = $this->svc->enqueuePhotos(10, ['correlation_id' => 'cid']);

        $this->assertInstanceOf(QueueJob::class, $job);
        $this->assertSame(QueueTypes::PHOTOS, $job->type);
        $this->assertSame('card', $job->entity);
        $this->assertSame(10, $job->entityId);

        $this->assertCount(1, $this->repo->jobs);
    }

    public function testHandleSuccessMarksDone(): void
    {
        $job = $this->svc->enqueuePhotos(10);
        $this->svc->handleSuccess($job);

        $this->assertSame('done', $this->repo->jobs[$job->id]->status);
    }

    public function testHandleFailureRetriesWhenAllowed(): void
    {
        $job = $this->svc->enqueuePhotos(10);
        $this->svc->handleFailure($job, ['message' => 'boom']);

        $stored = $this->repo->jobs[$job->id];
        $this->assertSame('retrying', $stored->status);
        $this->assertSame(1, $stored->attempts);
        $this->assertNotNull($stored->nextRetryAt);
        $this->assertCount(0, $this->dlq->jobs);
    }

    public function testHandleFailureMovesToDlqOnFatal(): void
    {
        $job = $this->svc->enqueuePhotos(10);
        $this->svc->handleFailure($job, ['message' => 'fatal', 'fatal' => true]);

        $stored = $this->repo->jobs[$job->id];
        $this->assertSame('dead', $stored->status);
        $this->assertCount(1, $this->dlq->jobs);
    }

    public function testHandleFailureMovesToDlqAfterAttemptsExhausted(): void
    {
        $job = $this->svc->enqueuePhotos(10);

        // пробиваем лимит ретраев (в policy лимит 5)
        for ($i=0; $i<6; $i++) {
            $this->svc->handleFailure($job, ['message' => 'boom']);
            $job = $this->repo->jobs[$job->id]; // обновляем попытки
        }

        $this->assertSame('dead', $job->status);
        $this->assertCount(1, $this->dlq->jobs);
    }
}

/**
 * Ниже — фейки, чтобы unit был чистым.
 * В проде используются реальные репозитории.
 */

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
        $job->attempts = 0;
        $job->status = 'queued';

        $this->jobs[$job->id] = $job;
        return $job;
    }

    public function fetchNext(string $type, string $workerId): ?QueueJob
    {
        foreach ($this->jobs as $j) {
            if ($j->type === $type && $j->status === 'queued') {
                $j->status = 'processing';
                $j->lockedBy = $workerId;
                return $j;
            }
        }
        return null;
    }

    public function markDone(int $id): void
    {
        $this->jobs[$id]->status = 'done';
    }

    public function markRetrying(int $id, int $attempts, \DateTimeImmutable $nextRetryAt, array $error): void
    {
        $j = $this->jobs[$id];
        $j->status = 'retrying';
        $j->attempts = $attempts;
        $j->nextRetryAt = $nextRetryAt;
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

    public function put(QueueJob $job): void
    {
        $this->jobs[] = $job;
    }
}
