<?php
// cabinet/tests/integration/parser.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\ParserWorker;
use App\Queues\QueueTypes;
use App\Queues\QueueJob;
use App\Adapters\Ports\ParserPort;
use App\Queues\QueueService;

use Backend\Modules\Parser\ParserService;
use Backend\Modules\Cards\CardsService;

final class ParserIntegrationTest extends TestCase
{
    public function testParserWorkerHappyPath(): void
    {
        $queueRepo = new FakeQueueRepoInt();
        $dlqRepo   = new FakeDlqRepoInt();
        $policy    = new \App\Queues\RetryPolicy();
        $logger    = new NullLogger();

        $queue = new QueueService($queueRepo, $dlqRepo, $policy, $logger);

        $cardsRepo  = new FakeCardsRepoInt();
        $parserRepo = new FakeParserRepoInt();

        $cards  = new CardsService($cardsRepo, $queue);
        $parser = new ParserService($parserRepo);

        $adapter = new FakeParserAdapter();

        // создаём карточку и триггерим parse
        $card = $cards->create(['title'=>'draft','source'=>'auto_ru'], 1);
        $cards->triggerParse($card->id, ['url'=>'https://auto.ru/cars/...','correlation_id'=>'cid-1']);

        // в очереди должен появиться job PARSER
        $job = $queueRepo->fetchNext(QueueTypes::PARSER, 'w1');
        $this->assertNotNull($job);

        // запускаем воркер
        $worker = new ParserWorker($queue, 'w1', $adapter, $parser, $cards, new NullWsEmitter());
        $worker->tick();

        // job done
        $storedJob = $queueRepo->jobs[$job->id];
        $this->assertSame('done', $storedJob->status);

        // parser payload done
        $payload = $parserRepo->payloads[$job->entityId];
        $this->assertSame('done', $payload->status);
        $this->assertSame('Audi A6, 2018', $payload->data['title']);

        // card updated to parser_done
        $updatedCard = $cardsRepo->cards[$card->id];
        $this->assertSame('parser_done', $updatedCard->status);
        $this->assertSame('Audi A6, 2018', $updatedCard->title);
        $this->assertCount(2, $updatedCard->photosRaw);
    }
}

/**
 * ----------------- Fakes -----------------
 */

final class FakeParserAdapter implements ParserPort
{
    public function normalizePush(array $push): array
    {
        return $push;
    }

    public function ingestRawPhotos(array $photoUrls, int $cardDraftId): array
    {
        $out = [];
        $order = 0;
        foreach ($photoUrls as $url) {
            $order++;
            $out[] = [
                'order' => $order,
                'raw_key' => "raw/{$cardDraftId}/{$order}.jpg",
                'raw_url' => "http://storage/raw/{$cardDraftId}/{$order}.jpg",
            ];
        }

        return $out;
    }

    public function poll(int $limit = 20): array
    {
        return [];
    }

    public function ack(string $externalId, array $meta = []): void
    {
    }
}

final class FakeQueueRepoInt
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

final class FakeDlqRepoInt { public array $jobs=[]; public function put(QueueJob $j): void { $this->jobs[]=$j; } }

final class FakeCardsRepoInt
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

final class FakeParserRepoInt
{
    public array $payloads=[];
    private int $seq=1;

    public function create(string $source,string $url,int $userId)
    {
        $m=\Backend\Modules\Parser\ParserModel::fromArray([
            'id'=>$this->seq++,
            'source'=>$source,
            'url'=>$url,
            'status'=>'queued',
            'data_json'=>null,
            'photos_json'=>[],
            'last_error'=>null,
            'created_by'=>$userId
        ]);
        $this->payloads[$m->id]=$m;
        return $m;
    }
    public function update(int $id,array $patch)
    {
        $m=$this->payloads[$id];
        foreach($patch as $k=>$v){ $m->$k=$v; }
        $this->payloads[$id]=$m;
        return $m;
    }
    public function getById(int $id){ return $this->payloads[$id] ?? null; }
}

final class NullLogger implements \Backend\Logger\LoggerInterface {
    public function info(string $m,array $c=[]): void {}
    public function warn(string $m,array $c=[]): void {}
    public function error(string $m,array $c=[]): void {}
    public function audit(string $t,string $m,array $c=[]): void {}
}
final class NullWsEmitter { public function emit(string $e,array $p=[]): void {} }
