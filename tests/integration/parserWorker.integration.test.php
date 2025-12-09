<?php
// cabinet/tests/integration/parserWorker.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\ParserWorker;
use App\Queues\QueueJob;
use App\Queues\QueueTypes;
use App\Queues\QueueService;
use App\Adapters\AdapterException;
use App\Adapters\Ports\ParserPort;
use App\Modules\Parser\ParserService;
use App\Modules\Cards\CardsService;
use App\WS\WsEmitter;

final class ParserWorkerIntegrationTest extends TestCase
{
    public function test_tick_moves_job_to_dlq_on_fatal_adapter_error(): void
    {
        $job = new QueueJob();
        $job->payload = ['push' => ['ad' => ['id' => 1], 'photos' => ['http://bad']]];
        $job->type = QueueTypes::PARSER;
        $job->entity = 'parser_payload';
        $job->entityId = 1;
        $job->id = 42;

        $adapter = $this->createMock(ParserPort::class);
        $adapter->method('normalizePush')->willReturn($job->payload['push']);
        $adapter->method('downloadBinary')->willThrowException(new AdapterException('boom', 'parser_photo_download', true));
        $adapter->method('guessExt')->willReturn(null);

        $queues = $this->createMock(QueueService::class);
        $queues->expects($this->exactly(2))
            ->method('fetchNext')
            ->with(QueueTypes::PARSER, 'worker-1')
            ->willReturnOnConsecutiveCalls($job, null);
        $queues->expects($this->never())->method('handleSuccess');
        $queues->expects($this->once())
            ->method('handleFailure')
            ->with($job, $this->callback(fn(array $error) => $error['code'] === 'parser_photo_download' && $error['fatal'] === true));

        $parserService = $this->createMock(ParserService::class);
        $cards = $this->createMock(CardsService::class);
        $ws = $this->createMock(WsEmitter::class);

        $worker = new class($queues, 'worker-1', $adapter, $parserService, $cards, $ws) extends ParserWorker {};

        $worker->tick();
    }
}
