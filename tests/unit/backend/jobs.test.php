<?php
// tests/unit/backend/jobs.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use Backend\Modules\Parser\ParserJobs;
use Backend\Modules\Photos\PhotosJobs;
use Backend\Modules\Export\ExportJobs;
use Backend\Modules\Publish\PublishJobs;

final class JobsDispatchTest extends TestCase
{
    public function testParserDispatchesWithCorrelation(): void
    {
        $queue = $this->createMock(QueueService::class);
        $queue->expects($this->once())
            ->method('enqueueParser')
            ->with(5, $this->callback(fn(array $payload) => ($payload['task_id'] ?? null) === 5 && ($payload['correlation_id'] ?? null) === 'cid-parser'))
            ->willReturn($this->job('parser'));

        $jobs = new ParserJobs($queue);
        $job = $jobs->dispatchParseRun(5, 'cid-parser');

        $this->assertSame('parser', $job->type);
    }

    public function testPhotosRetryDispatchesWithReasonForce(): void
    {
        $queue = $this->createMock(QueueService::class);
        $queue->expects($this->once())
            ->method('enqueuePhotos')
            ->with(10, $this->callback(function (array $payload) {
                return ($payload['task_id'] ?? null) === 20
                    && ($payload['reason'] ?? null) === 'oops'
                    && ($payload['force'] ?? null) === true
                    && ($payload['correlation_id'] ?? null) === 'cid-photos';
            }))
            ->willReturn($this->job('photos'));

        $jobs = new PhotosJobs($queue);
        $job = $jobs->dispatchPhotosRetry(10, 20, 'oops', true, 'cid-photos');

        $this->assertSame('photos', $job->type);
    }

    public function testExportDispatchRun(): void
    {
        $queue = $this->createMock(QueueService::class);
        $queue->expects($this->once())
            ->method('enqueueExport')
            ->with(7, $this->callback(fn(array $payload) => ($payload['export_id'] ?? null) === 7 && isset($payload['correlation_id'])))
            ->willReturn($this->job('export'));

        $jobs = new ExportJobs($queue);
        $job = $jobs->dispatchExportRun(7, 'cid-export');

        $this->assertSame('export', $job->type);
    }

    public function testPublishCancelDispatches(): void
    {
        $queue = $this->createMock(QueueService::class);
        $queue->expects($this->once())
            ->method('enqueuePublish')
            ->with(15, $this->callback(function (array $payload) {
                return ($payload['task_id'] ?? null) === 25
                    && ($payload['reason'] ?? null) === 'user_cancel'
                    && ($payload['correlation_id'] ?? null) === 'cid-publish';
            }))
            ->willReturn($this->job('publish'));

        $jobs = new PublishJobs($queue);
        $job = $jobs->dispatchPublishCancel(15, 25, 'user_cancel', 'cid-publish');

        $this->assertSame('publish', $job->type);
    }

    private function job(string $type): QueueJob
    {
        $j = new QueueJob();
        $j->id = 1;
        $j->type = $type;
        $j->entity = 'entity';
        $j->entityId = 1;
        $j->payload = [];
        return $j;
    }
}
