<?php
declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Workers\PublishWorker;
use App\Workers\RobotStatusWorker;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use App\Queues\QueueTypes;
use App\Adapters\Ports\MarketplacePort;
use App\Adapters\Ports\RobotPort;
use App\Adapters\Ports\RobotProfilePort;
use App\Modules\Cards\CardsService;
use App\Modules\Publish\PublishService;
use App\WS\WsEmitter;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;

final class PublishRobotPipelineTest extends TestCase
{
    public function testPublishWorkerEnqueuesRobotStatusThroughPipeline(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-publish-worker'));

        $queues = $this->createMock(QueueService::class);
        $avito = $this->createMock(MarketplacePort::class);
        $avito->method('mapCard')->willReturn(['mapped' => true]);

        $robot = $this->createMock(RobotPort::class);
        $robot->method('start')->willReturn(['session_id' => 'sess-1']);
        $robot->method('publish')->willReturn(['avito_item_id' => 'av-1']);

        $dolphin = $this->createMock(RobotProfilePort::class);
        $dolphin->method('allocateProfile')->willReturn(['profile_id' => 'prof-1']);
        $dolphin->method('startProfile')->willReturn(null);

        $publishService = $this->createMock(PublishService::class);
        $publishService->method('createJob')->willReturn(33);

        $cards = $this->createMock(CardsService::class);
        $cards->method('snapshotForPublish')->willReturn(['id' => 99]);

        $ws = $this->createMock(WsEmitter::class);
        $ws->expects($this->atLeastOnce())->method('emit');

        $pipeline = $this->createMock(JobDispatcher::class);
        $pipeline->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) {
                $payload = $job->payload()->toArray();
                return $job->type() === JobType::ROBOT_STATUS
                    && $payload['trace_id'] === 'trace-publish-worker'
                    && $payload['avito_item_id'] === 'av-1'
                    && $payload['session_id'] === 'sess-1';
            }))
            ->willReturn(new QueueJob());

        $worker = new class(
            $queues,
            'w-publish',
            $avito,
            $robot,
            $dolphin,
            $publishService,
            $cards,
            $ws,
            $pipeline
        ) extends PublishWorker {
            public function runHandle(QueueJob $job): void
            {
                $this->handle($job);
            }
        };

        $job = new QueueJob();
        $job->type = QueueTypes::PUBLISH;
        $job->entity = 'card';
        $job->entityId = 10;
        $job->payload = [
            'task_id' => 5,
            'correlation_id' => 'corr-publish',
            'trace_id' => 'trace-publish-worker',
        ];

        $worker->runHandle($job);
    }

    public function testRobotStatusWorkerEnqueuesRetryThroughPipeline(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-robot-worker'));

        $queues = $this->createMock(QueueService::class);
        $robot = $this->createMock(RobotPort::class);
        $robot->method('pollStatus')->willReturn(['status' => 'processing']);

        $dolphin = $this->createMock(RobotProfilePort::class);
        $dolphin->method('stopProfile')->willReturn(null);

        $avito = $this->createMock(MarketplacePort::class);
        $avito->method('normalizeStatus')->willReturn('processing');

        $publishService = $this->createMock(PublishService::class);
        $publishService->method('updateJobStatus')->willReturn([]);

        $ws = $this->createMock(WsEmitter::class);
        $ws->expects($this->atLeastOnce())->method('emit');

        $pipeline = $this->createMock(JobDispatcher::class);
        $pipeline->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) {
                $payload = $job->payload()->toArray();
                return $job->type() === JobType::ROBOT_STATUS
                    && $payload['trace_id'] === 'trace-robot-worker'
                    && $payload['avito_item_id'] === 'av-2';
            }))
            ->willReturn(new QueueJob());

        $worker = new class(
            $queues,
            'w-robot',
            $robot,
            $dolphin,
            $avito,
            $publishService,
            $ws,
            $pipeline
        ) extends RobotStatusWorker {
            public function runHandle(QueueJob $job): void
            {
                $this->handle($job);
            }
        };

        $job = new QueueJob();
        $job->type = QueueTypes::ROBOT_STATUS;
        $job->entity = 'publish_job';
        $job->entityId = 77;
        $job->payload = [
            'avito_item_id' => 'av-2',
            'session_id' => 'sess-2',
            'profile_id' => 'prof-2',
            'correlation_id' => 'corr-robot',
            'trace_id' => 'trace-robot-worker',
        ];

        $worker->runHandle($job);
    }
}
