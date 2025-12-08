<?php
// cabinet/tests/unit/backend/cards.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Modules\Cards\CardsService;
use Backend\Modules\Cards\CardsModel;
use App\Queues\QueueService;
use App\Queues\QueueTypes;

final class CardsServiceTest extends TestCase
{
    private CardsService $svc;
    private FakeCardsRepo $repo;
    private FakeQueueSvc $queue;

    protected function setUp(): void
    {
        $this->repo = new FakeCardsRepo();
        $this->queue = new FakeQueueSvc();

        // CardsService($pdo, QueueService) в проде; тут фейки
        $this->svc = new CardsService($this->repo, $this->queue);
    }

    public function testCreateCard(): void
    {
        $card = $this->svc->create([
            'title' => 'Audi A6',
            'source' => 'auto_ru',
        ], 7);

        $this->assertInstanceOf(CardsModel::class, $card);
        $this->assertSame('draft', $card->status);
        $this->assertSame('Audi A6', $card->title);

        $stored = $this->repo->getById($card->id);
        $this->assertSame($card->id, $stored->id);
    }

    public function testTriggerParseEnqueuesJobAndUpdatesStatus(): void
    {
        $card = $this->svc->create(['title'=>'x'], 1);

        $this->svc->triggerParse($card->id, ['url'=>'https://auto.ru/...']);

        $updated = $this->repo->getById($card->id);
        $this->assertSame('parser_queued', $updated->status);

        $this->assertCount(1, $this->queue->jobs);
        $this->assertSame(QueueTypes::PARSER, $this->queue->jobs[0]['type']);
        $this->assertSame('card', $this->queue->jobs[0]['entity']);
        $this->assertSame($card->id, $this->queue->jobs[0]['entity_id']);
    }

    public function testApplyParserPayloadUpdatesCard(): void
    {
        $card = $this->svc->create(['title'=>'x'], 1);

        $payload = [
            'title' => 'BMW X5',
            'description' => 'nice',
            'vehicle' => ['brand'=>'BMW'],
            'price' => ['value'=>100],
            'location' => ['city'=>'MSK'],
            'meta' => ['mileage'=>1],
        ];

        $this->svc->applyParserPayload($card->id, $payload);

        $updated = $this->repo->getById($card->id);
        $this->assertSame('parser_done', $updated->status);
        $this->assertSame('BMW X5', $updated->title);
        $this->assertSame('nice', $updated->description);
        $this->assertSame('BMW', $updated->vehicle['brand']);
    }
}

/**
 * -------- Fakes --------
 */

final class FakeCardsRepo
{
    /** @var array<int,CardsModel> */
    public array $cards = [];
    private int $seq = 1;

    public function create(array $data, int $userId): CardsModel
    {
        $m = CardsModel::fromArray([
            'id' => $this->seq++,
            'status' => 'draft',
            'title' => $data['title'] ?? null,
            'description' => null,
            'vehicle_json' => [],
            'price_json' => [],
            'location_json' => [],
            'meta_json' => [],
            'created_by' => $userId,
        ]);
        $this->cards[$m->id] = $m;
        return $m;
    }

    public function update(int $id, array $patch): CardsModel
    {
        $m = $this->cards[$id];
        foreach ($patch as $k=>$v) {
            $m->$k = $v;
        }
        $this->cards[$id] = $m;
        return $m;
    }

    public function getById(int $id): ?CardsModel
    {
        return $this->cards[$id] ?? null;
    }
}

final class FakeQueueSvc extends QueueService
{
    public array $jobs = [];

    public function __construct() {}

    public function enqueueParser(int $payloadId, array $payload = []): \App\Queues\QueueJob
    {
        $this->jobs[] = [
            'type' => QueueTypes::PARSER,
            'entity' => 'card',
            'entity_id' => $payloadId,
            'payload' => $payload
        ];

        $j = new \App\Queues\QueueJob();
        $j->id = count($this->jobs);
        $j->type = QueueTypes::PARSER;
        $j->entity = 'card';
        $j->entityId = $payloadId;
        $j->payload = $payload;
        $j->attempts = 0;
        $j->status = 'queued';

        return $j;
    }

    // остальные enqueue не нужны в этом тесте
}
