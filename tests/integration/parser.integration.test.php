<?php
// cabinet/tests/integration/parser.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\ParserWorker;
use App\Queues\QueueTypes;
use App\Queues\QueueJob;
use App\Adapters\Ports\ParserPort;
use App\Queues\QueueService;
use App\Application\Services\RawPhotosIngestService;

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
        $photosIngest = new FakePhotosIngestService();

        // создаём карточку и триггерим parse
        $card = $cards->create(['title'=>'draft','source'=>'auto_ru'], 1);
        $cards->triggerParse($card->id, ['url'=>'https://auto.ru/cars/...','correlation_id'=>'cid-1']);

        // в очереди должен появиться job PARSER
        $job = $queueRepo->fetchNext(QueueTypes::PARSER, 'w1');
        $this->assertNotNull($job);

        // запускаем воркер
        $worker = new ParserWorker($queue, 'w1', $adapter, $photosIngest, $parser, $cards, new NullWsEmitter());
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

    public function poll(int $limit = 20): array
    {
        return [];
    }

    public function ack(string $externalId, array $meta = []): void
    {
    }
}

final class FakePhotosIngestService extends RawPhotosIngestService
{
    public function __construct()
    {
        parent::__construct(new class implements \App\Application\Ports\PhotosIngestPort {
            public function download(string $url): string { return ''; }
            public function storeRaw(string $key, string $binary, string $extension): string { return ''; }
        });
    }

    public function ingest(array $photoUrls, int $cardDraftId): array
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

*** truncated below ***
