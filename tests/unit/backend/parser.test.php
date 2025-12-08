<?php
// cabinet/tests/unit/backend/parser.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Modules\Parser\ParserService;
use Backend\Modules\Parser\ParserModel;

final class ParserServiceTest extends TestCase
{
    private ParserService $svc;
    private FakeParserRepo $repo;

    protected function setUp(): void
    {
        $this->repo = new FakeParserRepo();
        $this->svc = new ParserService($this->repo);
    }

    public function testCreatePayload(): void
    {
        $p = $this->svc->createPayload('auto_ru', 'http://auto.ru/x', 7);

        $this->assertInstanceOf(ParserModel::class, $p);
        $this->assertSame('queued', $p->status);
        $this->assertSame('auto_ru', $p->source);
    }

    public function testMarkDoneAndFailed(): void
    {
        $p = $this->svc->createPayload('auto_ru', 'http://auto.ru/x', 7);

        $done = $this->svc->markDone($p->id, ['title'=>'ok'], [['url'=>'x','order_no'=>0]]);
        $this->assertSame('done', $done->status);
        $this->assertSame('ok', $done->data['title']);
        $this->assertCount(1, $done->photos);

        $p2 = $this->svc->createPayload('auto_ru', 'http://auto.ru/y', 7);
        $failed = $this->svc->markFailed($p2->id, ['message'=>'fail']);

        $this->assertSame('failed', $failed->status);
        $this->assertNotEmpty($failed->lastError);
    }
}

/**
 * -------- Fakes --------
 */
final class FakeParserRepo
{
    /** @var array<int,ParserModel> */
    public array $payloads = [];
    private int $seq = 1;

    public function create(string $source, string $url, int $userId): ParserModel
    {
        $m = ParserModel::fromArray([
            'id' => $this->seq++,
            'source' => $source,
            'url' => $url,
            'status' => 'queued',
            'data_json' => null,
            'photos_json' => [],
            'last_error' => null,
            'created_by' => $userId,
        ]);
        $this->payloads[$m->id] = $m;
        return $m;
    }

    public function update(int $id, array $patch): ParserModel
    {
        $m = $this->payloads[$id];
        foreach ($patch as $k=>$v) $m->$k = $v;
        $this->payloads[$id] = $m;
        return $m;
    }

    public function getById(int $id): ?ParserModel
    {
        return $this->payloads[$id] ?? null;
    }
}
