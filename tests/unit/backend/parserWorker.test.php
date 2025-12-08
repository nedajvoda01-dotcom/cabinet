<?php
// cabinet/tests/unit/backend/parserWorker.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\ParserWorker;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use App\Queues\QueueTypes;
use App\Adapters\ParserAdapter;
use App\Modules\Parser\ParserService;
use App\Modules\Cards\CardsService;
use App\WS\WsEmitter;

final class ParserWorkerUnitTest extends TestCase
{
    public function test_ingest_flow_uses_adapter_as_io_only(): void
    {
        $pushPayload = [
            'ad' => ['id' => 77],
            'photos' => ['http://x/1.jpg', 'http://x/2.png'],
        ];

        $adapter = $this->createMock(ParserAdapter::class);
        $adapter->expects($this->once())
            ->method('normalizePush')
            ->with($pushPayload)
            ->willReturn($pushPayload);
        $adapter->expects($this->exactly(2))
            ->method('downloadBinary')
            ->withConsecutive(['http://x/1.jpg'], ['http://x/2.png'])
            ->willReturnOnConsecutiveCalls('bin1', 'bin2');
        $adapter->expects($this->exactly(2))
            ->method('guessExt')
            ->withConsecutive(['http://x/1.jpg'], ['http://x/2.png'])
            ->willReturnOnConsecutiveCalls('jpg', 'png');
        $adapter->expects($this->exactly(2))
            ->method('uploadRaw')
            ->withConsecutive(
                ['raw/10/1.jpg', 'bin1', 'jpg'],
                ['raw/10/2.png', 'bin2', 'png']
            )
            ->willReturnOnConsecutiveCalls('http://s3/raw/10/1.jpg', 'http://s3/raw/10/2.png');

        $cards = $this->createMock(CardsService::class);
        $cards->expects($this->once())
            ->method('createDraftFromAd')
            ->with(['id' => 77])
            ->willReturn(10);

        $parserService = $this->createMock(ParserService::class);
        $parserService->expects($this->once())
            ->method('attachRawPhotos')
            ->with(10, [
                ['order' => 1, 'raw_key' => 'raw/10/1.jpg', 'raw_url' => 'http://s3/raw/10/1.jpg'],
                ['order' => 2, 'raw_key' => 'raw/10/2.png', 'raw_url' => 'http://s3/raw/10/2.png'],
            ]);

        $queues = $this->createMock(QueueService::class);
        $queues->expects($this->once())
            ->method('enqueuePhotos')
            ->with(10, ['source' => 'parser']);

        $ws = $this->createMock(WsEmitter::class);
        $ws->expects($this->once())
            ->method('emit')
            ->with('card.status.updated', [
                'card_id' => 10,
                'stage' => 'parser',
                'status' => 'ready',
            ]);

        $worker = new class($queues, 'w1', $adapter, $parserService, $cards, $ws) extends ParserWorker {
            public function runHandle(QueueJob $job): void
            {
                $this->handle($job);
            }
        };

        $job = new QueueJob();
        $job->payload = ['push' => $pushPayload];
        $job->type = QueueTypes::PARSER;
        $job->entity = 'parser_payload';
        $job->entityId = 99;
        $job->id = 1;

        $worker->runHandle($job);
    }
}
