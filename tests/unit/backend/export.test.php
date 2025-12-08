<?php
// cabinet/tests/unit/backend/export.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use Backend\Modules\Export\ExportService;
use Backend\Modules\Export\ExportModel;

final class ExportServiceTest extends TestCase
{
    private ExportService $svc;
    private FakeExportRepo $repo;

    protected function setUp(): void
    {
        $this->repo = new FakeExportRepo();
        $this->svc = new ExportService($this->repo);
    }

    public function testCreateExport(): void
    {
        $export = $this->svc->createExport([1,2,3], ['format' => 'avito_xml'], 7);

        $this->assertInstanceOf(ExportModel::class, $export);
        $this->assertSame('queued', $export->status);
        $this->assertSame([1,2,3], $export->cardIds);
        $this->assertSame('avito_xml', $export->options['format']);
    }

    public function testMarkDone(): void
    {
        $export = $this->svc->createExport([1], ['format'=>'avito_xml'], 7);
        $done = $this->svc->markDone($export->id, 'exports/e1.xml', 'http://storage/exports/e1.xml');

        $this->assertSame('done', $done->status);
        $this->assertSame('exports/e1.xml', $done->fileKey);
        $this->assertSame('http://storage/exports/e1.xml', $done->fileUrl);
    }

    public function testCancelExport(): void
    {
        $export = $this->svc->createExport([1], ['format'=>'avito_xml'], 7);
        $canceled = $this->svc->cancel($export->id);

        $this->assertSame('canceled', $canceled->status);
    }
}

/**
 * -------- Fakes --------
 */
final class FakeExportRepo
{
    /** @var array<int,ExportModel> */
    public array $exports = [];
    private int $seq = 1;

    public function create(array $cardIds, array $options, int $userId): ExportModel
    {
        $m = ExportModel::fromArray([
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

    public function update(int $id, array $patch): ExportModel
    {
        $m = $this->exports[$id];
        foreach ($patch as $k=>$v) $m->$k = $v;
        $this->exports[$id] = $m;
        return $m;
    }

    public function getById(int $id): ?ExportModel
    {
        return $this->exports[$id] ?? null;
    }
}
