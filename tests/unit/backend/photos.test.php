<?php
// cabinet/tests/unit/backend/photos.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Modules\Photos\PhotosService;
use Backend\Modules\Photos\PhotosModel;

final class PhotosServiceTest extends TestCase
{
    private PhotosService $svc;
    private FakePhotosRepo $repo;

    protected function setUp(): void
    {
        $this->repo = new FakePhotosRepo();
        $this->svc = new PhotosService($this->repo);
    }

    public function testCreateRawPhotos(): void
    {
        $photos = $this->svc->createRawPhotos(10, [
            ['url'=>'http://x/0.jpg', 'order_no'=>0],
            ['url'=>'http://x/1.jpg', 'order_no'=>1],
        ]);

        $this->assertCount(2, $photos);
        $this->assertSame(10, $photos[0]->cardId);
        $this->assertSame('raw', $photos[0]->status);
        $this->assertSame(0, $photos[0]->orderNo);

        $stored = $this->repo->listByCard(10);
        $this->assertCount(2, $stored);
    }

    public function testSetPrimaryPhoto(): void
    {
        $this->svc->createRawPhotos(10, [
            ['url'=>'http://x/0.jpg', 'order_no'=>0],
            ['url'=>'http://x/1.jpg', 'order_no'=>1],
        ]);

        $primary = $this->svc->setPrimary(10, 1);

        $this->assertTrue($primary->isPrimary);

        $stored = $this->repo->listByCard(10);
        $this->assertFalse($stored[0]->isPrimary);
        $this->assertTrue($stored[1]->isPrimary);
    }

    public function testMarkMaskedFinishesPipeline(): void
    {
        $this->svc->createRawPhotos(10, [
            ['url'=>'http://x/0.jpg', 'order_no'=>0],
            ['url'=>'http://x/1.jpg', 'order_no'=>1],
        ]);

        $this->svc->markMasked(10, 0, 'masked/0.jpg', 'http://y/0.jpg');
        $this->svc->markMasked(10, 1, 'masked/1.jpg', 'http://y/1.jpg');

        $stored = $this->repo->listByCard(10);
        $this->assertSame('masked', $stored[0]->status);
        $this->assertSame('masked', $stored[1]->status);

        $this->assertTrue($this->svc->isAllMasked(10));
    }
}

/**
 * -------- Fakes --------
 */

final class FakePhotosRepo
{
    /** @var array<int,PhotosModel[]> */
    public array $byCard = [];

    public function create(int $cardId, int $orderNo, string $rawUrl): PhotosModel
    {
        $m = PhotosModel::fromArray([
            'id' => count($this->byCard[$cardId] ?? []) + 1,
            'card_id' => $cardId,
            'order_no' => $orderNo,
            'raw_url' => $rawUrl,
            'status' => 'raw',
            'is_primary' => false,
        ]);
        $this->byCard[$cardId][] = $m;
        return $m;
    }

    /** @return PhotosModel[] */
    public function listByCard(int $cardId): array
    {
        return $this->byCard[$cardId] ?? [];
    }

    public function updateByCardOrder(int $cardId, int $orderNo, array $patch): PhotosModel
    {
        foreach ($this->byCard[$cardId] as $i => $m) {
            if ($m->orderNo === $orderNo) {
                foreach ($patch as $k=>$v) {
                    $m->$k = $v;
                }
                $this->byCard[$cardId][$i] = $m;
                return $m;
            }
        }
        throw new \RuntimeException("photo not found");
    }

    public function clearPrimary(int $cardId): void
    {
        foreach ($this->byCard[$cardId] as $i => $m) {
            $m->isPrimary = false;
            $this->byCard[$cardId][$i] = $m;
        }
    }
}
