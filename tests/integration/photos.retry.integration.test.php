<?php
// tests/integration/photos.retry.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\PhotosWorker;
use App\Queues\QueueTypes;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use App\Queues\RetryPolicy;
use App\Adapters\AdapterException;
use App\Adapters\Ports\PhotoProcessorPort;
use App\Adapters\Fakes\FakeStorageAdapter;
use App\WS\WsEmitter;
use App\WS\WsServerInterface;

final class PhotosRetryIntegrationTest extends TestCase
{
    public function testTransientAdapterErrorRetries(): void
    {
        $queueRepo = new FakeQueueRepoRetry();
        $dlqRepo = new FakeDlqRepoRetry();
        $policy = new RetryPolicy(2, [10, 20]);
        $logger = new NullLoggerPhotosRetry();
        $queues = new QueueService($queueRepo, $dlqRepo, $policy, $logger);

        $job = $queueRepo->enqueue(QueueTypes::PHOTOS, 'card', 101, ['correlation_id' => 'corr-1']);
        $worker = new PhotosWorker(
            $queues,
            'w-retry',
            new FailingPhotoAdapterRetry(false),
            new FakeStorageAdapter(),
            new FakePhotosServiceRetry(),
            new FakeExportServiceRetry(),
            new WsEmitter(new NullWsServerPhotosRetry())
        );

        $worker->tick();

        $this->assertSame('retrying', $queueRepo->jobs[$job->id]->status);
        $this->assertCount(0, $dlqRepo->jobs);
    }

    public function testFatalAdapterErrorGoesDlq(): void
    {
        $queueRepo = new FakeQueueRepoRetry();
        $dlqRepo = new FakeDlqRepoRetry();
        $policy = new RetryPolicy(2, [10, 20]);
        $logger = new NullLoggerPhotosRetry();
        $queues = new QueueService($queueRepo, $dlqRepo, $policy, $logger);

        $job = $queueRepo->enqueue(QueueTypes::PHOTOS, 'card', 102, ['correlation_id' => 'corr-2']);
        $worker = new PhotosWorker(
            $queues,
            'w-retry',
            new FailingPhotoAdapterRetry(true),
            new FakeStorageAdapter(),
            new FakePhotosServiceRetry(),
            new FakeExportServiceRetry(),
            new WsEmitter(new NullWsServerPhotosRetry())
        );

        $worker->tick();

        $this->assertSame('dead', $queueRepo->jobs[$job->id]->status);
        $this->assertCount(1, $dlqRepo->jobs);
    }
}

final class FailingPhotoAdapterRetry implements PhotoProcessorPort
{
    public function __construct(private bool $fatal)
    {
    }

    public function maskPhoto(string $rawUrl, array $maskParams = [], ?string $idempotencyKey = null): array
    {
        throw new AdapterException(
            $this->fatal ? 'fatal contract' : 'timeout',
            $this->fatal ? 'contract_mismatch' : 'timeout',
            !$this->fatal,
            ['status' => $this->fatal ? 422 : 503]
        );
    }

    public function health(): array
    {
        return ['ok' => true];
    }
}

final class FakePhotosServiceRetry
{
    public function getRawPhotos(int $cardId): array
    {
        return [
            ['order' => 1, 'raw_url' => 'http://example/raw.jpg', 'mask_params' => []],
        ];
    }

    public function attachMaskedPhotos(int $cardId, array $masked): void {}
    public function markStageDone(int $cardId): void {}
}

final class FakeExportServiceRetry
{
    public function createExport(int $cardId): int
    {
        return 500 + $cardId;
    }
}

final class FakeQueueRepoRetry
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

final class FakeDlqRepoRetry { public array $jobs=[]; public function put(QueueJob $j): void { $this->jobs[]=$j; } }

final class NullLoggerPhotosRetry implements \Backend\Logger\LoggerInterface
{
    public function info(string $msg, array $context = []): void {}
    public function warn(string $msg, array $context = []): void {}
    public function error(string $msg, array $context = []): void {}
}

final class NullWsServerPhotosRetry implements WsServerInterface
{
    public function broadcast(array $message): void {}
    public function sendTo(string $clientId, array $message): void {}
}
