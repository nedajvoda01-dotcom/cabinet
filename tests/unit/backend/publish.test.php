<?php
// cabinet/tests/unit/backend/publish.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Modules\Publish\PublishService;
use Backend\Modules\Publish\PublishModel;

final class PublishServiceTest extends TestCase
{
    private PublishService $svc;
    private FakePublishRepo $repo;

    protected function setUp(): void
    {
        $this->repo = new FakePublishRepo();
        $this->svc = new PublishService($this->repo);
    }

    public function testCreatePublishJob(): void
    {
        $job = $this->svc->createJob(10, 7, ['export_id'=>3]);

        $this->assertInstanceOf(PublishModel::class, $job);
        $this->assertSame(10, $job->cardId);
        $this->assertSame('queued', $job->status);
    }

    public function testMarkRunningDoneFailed(): void
    {
        $job = $this->svc->createJob(10, 7);

        $running = $this->svc->markRunning($job->id);
        $this->assertSame('running', $running->status);

        $done = $this->svc->markDone($job->id, 'avito-123');
        $this->assertSame('done', $done->status);
        $this->assertSame('avito-123', $done->avitoItemId);

        $job2 = $this->svc->createJob(11, 7);
        $failed = $this->svc->markFailed($job2->id, ['message'=>'boom']);
        $this->assertSame('failed', $failed->status);
        $this->assertNotEmpty($failed->lastError);
    }
}

/**
 * -------- Fakes --------
 */
final class FakePublishRepo
{
    /** @var array<int,PublishModel> */
    public array $jobs = [];
    private int $seq = 1;

    public function create(int $cardId, int $userId, array $meta): PublishModel
    {
        $m = PublishModel::fromArray([
            'id' => $this->seq++,
            'card_id' => $cardId,
            'status' => 'queued',
            'meta_json' => $meta,
            'avito_item_id' => null,
            'last_error' => null,
            'created_by' => $userId,
        ]);
        $this->jobs[$m->id] = $m;
        return $m;
    }

    public function update(int $id, array $patch): PublishModel
    {
        $m = $this->jobs[$id];
        foreach ($patch as $k=>$v) $m->$k = $v;
        $this->jobs[$id] = $m;
        return $m;
    }

    public function getById(int $id): ?PublishModel
    {
        return $this->jobs[$id] ?? null;
    }
}
