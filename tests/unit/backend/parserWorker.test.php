<?php
// cabinet/tests/unit/backend/parserWorker.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\ParserWorker;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use App\Queues\QueueTypes;
use App\Adapters\Ports\ParserPort;
use App\Application\Services\RawPhotosIngestService;
use App\Modules\Parser\ParserService;
use App\Modules\Cards\CardsService;
use App\WS\WsEmitter;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;

final class ParserWorkerUnitTest extends TestCase
{
    public function test_ingest_flow_delegates_to_service_and_keeps_adapter_thin(): void
    {
        $pushPayload = [
            'ad' => ['id' => 77],
            'photos' => ['http://x/1.jpg', 'http://x/2.png'],
        ];

        $adapter = $this->createMock(ParserPort::class);
        $adapter->expects($this->once())
            ->method('normalizePush')
            ->with($pushPayload)
            ->willReturn($pushPayload);

        $ingestService = $this->createMock(RawPhotosIngestService::class);
        $ingestService->expects($this->once())
            ->method('ingest')
            ->with($pushPayload['photos'], 10)
            ->willReturn([
                ['order' => 1, 'raw_key' => 'raw/10/1.jpg', 'raw_url' => 'http://s3/raw/10/1.jpg'],
                ['order' => 2, 'raw_key' => 'raw/10/2.png', 'raw_url' => 'http://s3/raw/10/2.png'],
            ]);

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

        TraceContext::setCurrent(TraceContext::fromString('trace-worker'));

        $pipeline = $this->createMock(JobDispatcher::class);
        $pipeline->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) {
                $payload = $job->payload()->toArray();
                return $job->type() === JobType::PHOTOS
                    && $payload['source'] === 'parser'
                    && $payload['correlation_id'] === 'corr-1'
                    && $payload['trace_id'] === 'trace-worker';
            }));

        $queues = $this->createMock(QueueService::class);

        $ws = $this->createMock(WsEmitter::class);
        $ws->expects($this->atLeastOnce())
            ->method('emit')
            ->with('pipeline.stage', $this->callback(function ($payload) {
                return $payload['stage'] === 'parser'
                    && $payload['status'] === 'done'
                    && $payload['card_id'] === 10
                    && isset($payload['correlation_id'])
                    && $payload['correlation_id'] === 'corr-1';
            }));

        $worker = new class($queues, 'w1', $adapter, $ingestService, $parserService, $cards, $ws, $pipeline) extends ParserWorker {
            public function runHandle(QueueJob $job): void
            {
                $this->handle($job);
            }
        };

        $job = new QueueJob();
        $job->payload = ['push' => $pushPayload, 'task_id' => 5, 'correlation_id' => 'corr-1'];
        $job->type = QueueTypes::PARSER;
        $job->entity = 'parser_payload';
        $job->entityId = 99;
        $job->id = 1;

        $worker->runHandle($job);
    }
}
