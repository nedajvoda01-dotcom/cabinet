<?php
// cabinet/tests/integration/export.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\ExportWorker;
use App\Queues\QueueTypes;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use App\Adapters\Ports\StoragePort;

use Backend\Modules\Export\ExportService;
use Backend\Modules\Cards\CardsService;
use Backend\Modules\Publish\PublishService;
use Backend\Modules\Export\ExportModel;

final class ExportIntegrationTest extends TestCase
{
    public function testExportWorkerGeneratesXmlUploadsAndEnqueuesPublish(): void
    {
        $queueRepo = new FakeQueueRepoExportInt();
        $dlqRepo   = new FakeDlqRepoExportInt();
        $policy    = new \App\Queues\RetryPolicy();
        $logger    = new NullLoggerExportInt();

        $queue = new QueueService($queueRepo, $dlqRepo, $policy, $logger);

        $exportRepo = new FakeExportRepoExportInt();
        $cardsRepo  = new FakeCardsRepoExportInt();

        $cardsSvc  = new CardsService($cardsRepo, $queue);
        $exportSvc = new ExportService($exportRepo);

        $builder   = new FakeAvitoXmlBuilderExportInt();
        $s3        = new FakeS3AdapterExportInt();
        $publishSvc = $this->createMock(PublishService::class);

        // подготовка: две карточки готовы к экспорту
        $c1 = $cardsSvc->create(['title'=>'car1','source'=>'auto_ru'], 1);
        $c2 = $cardsSvc->create(['title'=>'car2','source'=>'auto_ru'], 1);

        $cardsRepo->cards[$c1->id]->status = 'photos_done';
        $cardsRepo->cards[$c2->id]->status = 'photos_done';

        // создаём export и ставим EXPORT job
        $export = $exportSvc->createExport([$c1->id, $c2->id], ['format'=>'avito_xml'], 1);
        $queue->enqueueExport($export->id, ['correlation_id'=>'cid-exp-1']);

        $job = $queueRepo->fetchNext(QueueTypes::EXPORT, 'w-exp-1');
        $this->assertNotNull($job);

        // воркер
        $worker = new ExportWorker(
            $queue,
            'w-exp-1',
            $s3,
            $exportSvc,
            $publishSvc,
            new NullWsEmitterExportInt()
        );

        $worker->tick();

        // job done
        $storedJob = $queueRepo->jobs[$job->id];
        $this->assertSame('done', $storedJob->status);

        // export done + файл в storage
        $storedExport = $exportRepo->exports[$export->id];
        $this->assertSame('done', $storedExport->status);
        $this->assertNotEmpty($storedExport->fileKey);
        $this->assertNotEmpty($storedExport->fileUrl);

        $this->assertCount(1, $s3->puts, "XML должен быть загружен в storage");

        // publish jobs поставлены на карточки
        $this->assertTrue($queueRepo->hasPublishForCard($c1->id));
        $this->assertTrue($queueRepo->hasPublishForCard($c2->id));
    }
}

/**
 * ----------------- Fakes -----------------
 */

final class FakeAvitoXmlBuilderExportInt
{
    public function build(array $cards): string
    {
        // упрощённый XML для теста
        $items = "";
        foreach ($cards as $c) {
            $items .= "<Ad><Title>{$c->title}</Title></Ad>";
        }
        return "<Ads>{$items}</Ads>";
    }
}

final class FakeS3AdapterExportInt implements StoragePort
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

final class FakeQueueRepoExportInt
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

    public function hasPublishForCard(int $cardId): bool
    {
        foreach ($this->jobs as $j) {
            if ($j->type === QueueTypes::PUBLISH && $j->entity === 'card' && $j->entityId === $cardId) {
                return true;
            }
        }
        return false;
    }

    public function hasJobType(string $type): bool
    {
        foreach ($this->jobs as $j) if ($j->type === $type) return true;
        return false;
    }
}

final class FakeDlqRepoExportInt { public array $jobs=[]; public function put(QueueJob $j): void { $this->jobs[]=$j; } }

final class FakeCardsRepoExportInt
{
    public array $cards = [];
    private int $seq=1;

    public function create(array $data, int $userId)
    {
        $m = \Backend\Modules\Cards\CardsModel::fromArray([
            'id'=>$this->seq++,
            'status'=>'draft',
            'title'=>$data['title'] ?? null,
            'description'=>null,
            'vehicle_json'=>[],
            'price_json'=>[],
            'location_json'=>[],
            'meta_json'=>[],
            'photos_raw'=>[],
            'created_by'=>$userId
        ]);
        $this->cards[$m->id]=$m;
        return $m;
    }

    public function update(int $id, array $patch)
    {
        $m=$this->cards[$id];
        foreach($patch as $k=>$v){ $m->$k=$v; }
        $this->cards[$id]=$m;
        return $m;
    }

    public function getById(int $id){ return $this->cards[$id] ?? null; }
}

final class FakeExportRepoExportInt
{
    public array $exports=[];
    private int $seq=1;

    public function create(array $cardIds, array $options, int $userId): ExportModel
    {
        $m = ExportModel::fromArray([
            'id'=>$this->seq++,
            'status'=>'queued',
            'card_ids'=>$cardIds,
            'options_json'=>$options,
            'file_key'=>null,
            'file_url'=>null,
            'created_by'=>$userId
        ]);
        $this->exports[$m->id]=$m;
        return $m;
    }

    public function update(int $id, array $patch): ExportModel
    {
        $m=$this->exports[$id];
        foreach($patch as $k=>$v){ $m->$k=$v; }
        $this->exports[$id]=$m;
        return $m;
    }

    public function getById(int $id){ return $this->exports[$id] ?? null; }
}

final class NullLoggerExportInt implements \Backend\Logger\LoggerInterface {
    public function info(string $m,array $c=[]): void {}
    public function warn(string $m,array $c=[]): void {}
    public function error(string $m,array $c=[]): void {}
    public function audit(string $t,string $m,array $c=[]): void {}
}
final class NullWsEmitterExportInt { public function emit(string $e,array $p=[]): void {} }
