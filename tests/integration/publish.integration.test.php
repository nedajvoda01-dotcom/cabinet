<?php
// cabinet/tests/integration/publish.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\PublishWorker;
use App\Workers\RobotStatusWorker;
use App\Queues\QueueTypes;
use App\Queues\QueueJob;
use App\Queues\QueueService;

use Backend\Modules\Publish\PublishService;
use Backend\Modules\Cards\CardsService;
use Backend\Modules\Publish\PublishModel;

final class PublishIntegrationTest extends TestCase
{
    public function testPublishWorkerHappyPathPublishesCard(): void
    {
        $queueRepo = new FakeQueueRepoPublishInt();
        $dlqRepo   = new FakeDlqRepoPublishInt();
        $policy    = new \App\Queues\RetryPolicy();
        $logger    = new NullLoggerPublishInt();

        $queue = new QueueService($queueRepo, $dlqRepo, $policy, $logger);

        $cardsRepo   = new FakeCardsRepoPublishInt();
        $publishRepo = new FakePublishRepoPublishInt();

        $cardsSvc   = new CardsService($cardsRepo, $queue);
        $publishSvc = new PublishService($publishRepo);

        $dolphin = new FakeDolphinAdapterPublishInt();
        $robot   = new FakeRobotAdapterPublishInt();

        // подготовка: карточка готова к publish
        $card = $cardsSvc->create(['title'=>'car1', 'source'=>'auto_ru'], 1);
        $cardsRepo->cards[$card->id]->status = 'publish_queued';

        // создаём publish job и ставим PUBLISH
        $pjob = $publishSvc->createJob($card->id, 1, [
            'export_url' => 'http://storage.local/exports/e1.xml'
        ]);
        $queue->enqueuePublish($card->id, ['publish_job_id' => $pjob->id, 'correlation_id'=>'cid-pub-1']);

        $job = $queueRepo->fetchNext(QueueTypes::PUBLISH, 'w-pub-1');
        $this->assertNotNull($job);

        // запускаем PublishWorker
        $pubWorker = new PublishWorker(
            $queue,
            'w-pub-1',
            $dolphin,
            $robot,
            $publishSvc,
            $cardsSvc,
            new NullWsEmitterPublishInt()
        );
        $pubWorker->tick();

        // после tick PublishWorker должен:
        //  - пометить publish_job running
        //  - создать ROBOT_STATUS job
        $storedPublish = $publishRepo->jobs[$pjob->id];
        $this->assertSame('running', $storedPublish->status);
        $this->assertNotEmpty($storedPublish->meta['robot_run_id']);

        $this->assertTrue($queueRepo->hasJobType(QueueTypes::ROBOT_STATUS));

        // запускаем RobotStatusWorker (эмулируем завершение робота)
        $rsJob = $queueRepo->fetchNext(QueueTypes::ROBOT_STATUS, 'w-rs-1');
        $this->assertNotNull($rsJob);

        $rsWorker = new RobotStatusWorker(
            $queue,
            'w-rs-1',
            $robot,
            $publishSvc,
            $cardsSvc,
            new NullWsEmitterPublishInt()
        );
        $rsWorker->tick();

        // publish_job должен стать done и получить avito_item_id
        $storedPublish2 = $publishRepo->jobs[$pjob->id];
        $this->assertSame('done', $storedPublish2->status);
        $this->assertSame('avito-777', $storedPublish2->avitoItemId);

        // card должен стать published
        $updatedCard = $cardsRepo->cards[$card->id];
        $this->assertSame('published', $updatedCard->status);
    }
}

/**
 * ----------------- Fakes -----------------
 */

final class FakeDolphinAdapterPublishInt
{
    public function openSession(string $profileId, string $correlationId): array
    {
        return [
            'status' => 'ok',
            'session_id' => 'sess-123',
        ];
    }
}

final class FakeRobotAdapterPublishInt
{
    public array $runs = [];

    public function publish(string $sessionId, string $xmlUrl, array $photos, string $correlationId): array
    {
        $runId = 'run-' . (count($this->runs) + 1);
        $this->runs[$runId] = ['status'=>'queued','xml'=>$xmlUrl];
        return [
            'status' => 'ok',
            'run_id' => $runId,
        ];
    }

    public function getRunStatus(string $runId, string $correlationId): array
    {
        // эмулируем что всё сразу успешно
        return [
            'status' => 'done',
            'progress' => 1,
            'avito_item_id' => 'avito-777'
        ];
    }
}

final class FakeQueueRepoPublishInt
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
        foreach ($this->jobs as $j) if ($j->type === $type) return true;
        return false;
    }
}

final class FakeDlqRepoPublishInt { public array $jobs=[]; public function put(QueueJob $j): void { $this->jobs[]=$j; } }

final class FakeCardsRepoPublishInt
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

final class FakePublishRepoPublishInt
{
    /** @var array<int,PublishModel> */
    public array $jobs=[];
    private int $seq=1;

    public function create(int $cardId, int $userId, array $meta): PublishModel
    {
        $m = PublishModel::fromArray([
            'id'=>$this->seq++,
            'card_id'=>$cardId,
            'status'=>'queued',
            'meta_json'=>$meta,
            'avito_item_id'=>null,
            'last_error'=>null,
            'created_by'=>$userId
        ]);
        $this->jobs[$m->id]=$m;
        return $m;
    }

    public function update(int $id, array $patch): PublishModel
    {
        $m=$this->jobs[$id];
        foreach($patch as $k=>$v){ $m->$k=$v; }
        $this->jobs[$id]=$m;
        return $m;
    }

    public function getById(int $id){ return $this->jobs[$id] ?? null; }
}

final class NullLoggerPublishInt implements \Backend\Logger\LoggerInterface {
    public function info(string $m,array $c=[]): void {}
    public function warn(string $m,array $c=[]): void {}
    public function error(string $m,array $c=[]): void {}
    public function audit(string $t,string $m,array $c=[]): void {}
}
final class NullWsEmitterPublishInt { public function emit(string $e,array $p=[]): void {} }
