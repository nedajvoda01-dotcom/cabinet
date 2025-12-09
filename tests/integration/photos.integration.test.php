<?php
// cabinet/tests/integration/photos.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\PhotosWorker;
use App\Queues\QueueTypes;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use App\Adapters\Ports\PhotoProcessorPort;
use App\Adapters\Ports\StoragePort;

use Backend\Modules\Photos\PhotosService;
use Backend\Modules\Export\ExportService;

final class PhotosIntegrationTest extends TestCase
{
    public function testPhotosWorkerHappyPathMasksAndEnqueuesExport(): void
    {
        $queueRepo = new FakeQueueRepoPhotosInt();
        $dlqRepo   = new FakeDlqRepoPhotosInt();
        $policy    = new \App\Queues\RetryPolicy();
        $logger    = new NullLoggerPhotosInt();

        $queue = new QueueService($queueRepo, $dlqRepo, $policy, $logger);

        $photosRepo = new FakePhotosRepoPhotosInt();
        $exportRepo = new FakeExportRepoPhotosInt();

        $photosSvc = new PhotosService($photosRepo);
        $exportSvc = new ExportService($exportRepo);

        $photoApiAdapter = new FakePhotoApiAdapterPhotosInt();
        $s3Adapter        = new FakeS3AdapterPhotosInt();

        // подготовка: raw-фото у карточки
        $cardId = 10;
        $photosSvc->createRawPhotos($cardId, [
            ['url'=>'http://storage/raw/0.jpg', 'order_no'=>0],
            ['url'=>'http://storage/raw/1.jpg', 'order_no'=>1],
        ]);

        // ставим PHOTOS job
        $queue->enqueuePhotos($cardId, ['correlation_id'=>'cid-photos-1']);

        $job = $queueRepo->fetchNext(QueueTypes::PHOTOS, 'w-photos-1');
        $this->assertNotNull($job);

        // запускаем воркер
        $worker = new PhotosWorker(
            $queue,
            'w-photos-1',
            $photoApiAdapter,
            $s3Adapter,
            $photosSvc,
            $exportSvc,
            new NullWsEmitterPhotosInt()
        );

        $worker->tick();

        // job done
        $storedJob = $queueRepo->jobs[$job->id];
        $this->assertSame('done', $storedJob->status);

        // все фото стали masked
        $storedPhotos = $photosRepo->listByCard($cardId);
        $this->assertCount(2, $storedPhotos);
        $this->assertSame('masked', $storedPhotos[0]->status);
        $this->assertSame('masked', $storedPhotos[1]->status);
        $this->assertNotEmpty($storedPhotos[0]->maskedUrl);
        $this->assertNotEmpty($storedPhotos[1]->maskedUrl);

        // Export job поставлен (после полного маскирования)
        $this->assertTrue($queueRepo->hasJobType(QueueTypes::EXPORT));
    }
}

/**
 * ----------------- Fakes -----------------
 */

final class FakePhotoApiAdapterPhotosInt implements PhotoProcessorPort
{
    /** @var array<int,array> */
    public array $inputs = [];

    public function maskPhoto(string $rawUrl, array $maskParams = []): array
    {
        $this->inputs[] = ['raw_url' => $rawUrl, 'params' => $maskParams];

        return [
            'masked_url' => 'http://storage/masked/' . (count($this->inputs)),
            'meta' => ['source' => 'fake'],
        ];
    }

    public function health(): array
    {
        return ['ok' => true];
    }
}

final class FakeS3AdapterPhotosInt implements StoragePort
{
    public array $puts = [];

    public function putObject(string $key, string $binary, string $contentType = 'application/octet-stream'): void
    {
        $this->puts[] = compact('key', 'binary', 'contentType');
    }

    public function publicUrl(string $key): string
    {
        return 'http://storage.local/' . $key;
    }

    public function presignGet(string $key, int $expiresSec = 3600): string
    {
        return 'http://signed/' . $key;
    }

    public function listPrefix(string $prefix): array
    {
        return array_column($this->puts, 'key');
    }

    public function getObject(string $key): string
    {
        return '';
    }
}

final class FakeQueueRepoPhotosInt
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

    public function hasJobType(string $type): bool
    {
        foreach ($this->jobs as $j) {
            if ($j->type === $type) return true;
        }
        return false;
    }
}

final class FakeDlqRepoPhotosInt { public array $jobs=[]; public function put(QueueJob $j): void { $this->jobs[]=$j; } }

final class FakePhotosRepoPhotosInt
{
    /** @var array<int,\Backend\Modules\Photos\PhotosModel[]> */
    public array $byCard = [];

    public function create(int $cardId, int $orderNo, string $rawUrl)
    {
        $m = \Backend\Modules\Photos\PhotosModel::fromArray([
            'id' => count($this->byCard[$cardId] ?? []) + 1,
            'card_id' => $cardId,
            'order_no' => $orderNo,
            'raw_url' => $rawUrl,
            'status' => 'raw',
            'masked_key' => null,
            'masked_url' => null,
            'is_primary' => false,
        ]);
        $this->byCard[$cardId][] = $m;
        return $m;
    }

    public function listByCard(int $cardId): array
    {
        return $this->byCard[$cardId] ?? [];
    }

    public function updateByCardOrder(int $cardId, int $orderNo, array $patch)
    {
        foreach ($this->byCard[$cardId] as $i => $m) {
            if ($m->orderNo === $orderNo) {
                foreach ($patch as $k=>$v) $m->$k = $v;
                $this->byCard[$cardId][$i] = $m;
                return $m;
            }
        }
        throw new \RuntimeException("photo not found");
    }

    public function clearPrimary(int $cardId): void {}
}

final class FakeExportRepoPhotosInt
{
    public array $exports = [];
    private int $seq = 1;

    public function create(array $cardIds, array $options, int $userId)
    {
        $m = \Backend\Modules\Export\ExportModel::fromArray([
            'id' => $this->seq++,
            'status' => 'queued',
            'card_ids' => $cardIds,
            'options_json' => $options,
            'file_key' => null,
            'file_url' => null,
            'created_by' => $userId,
        ]);
        $this->exports[$m->id] = $m;
        return $m;
    }

    public function update(int $id, array $patch)
    {
        $m = $this->exports[$id];
        foreach ($patch as $k=>$v) $m->$k = $v;
        $this->exports[$id] = $m;
        return $m;
    }

    public function getById(int $id) { return $this->exports[$id] ?? null; }
}

final class NullLoggerPhotosInt implements \Backend\Logger\LoggerInterface {
    public function info(string $m,array $c=[]): void {}
    public function warn(string $m,array $c=[]): void {}
    public function error(string $m,array $c=[]): void {}
    public function audit(string $t,string $m,array $c=[]): void {}
}
final class NullWsEmitterPhotosInt { public function emit(string $e,array $p=[]): void {} }
