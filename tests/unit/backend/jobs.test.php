<?php
// tests/unit/backend/jobs.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Queues\QueueJob;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;
use Backend\Modules\Parser\ParserJobs;
use Backend\Modules\Photos\PhotosJobs;
use Backend\Modules\Export\ExportJobs;
use Backend\Modules\Publish\PublishJobs;

final class JobsDispatchTest extends TestCase
{
    public function testParserDispatchesWithCorrelation(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-parser'));

        $dispatcher = $this->createMock(JobDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) {
                $payload = $job->payload()->toArray();
                return $job->type() === JobType::PARSER
                    && $payload['task_id'] === 5
                    && $payload['correlation_id'] === 'cid-parser'
                    && $payload['trace_id'] === 'trace-parser';
            }))
            ->willReturn($this->job('parser'));

        $jobs = new ParserJobs($dispatcher);
        $job = $jobs->dispatchParseRun(5, 'cid-parser');

        $this->assertSame('parser', $job->type);
    }

    public function testPhotosRetryDispatchesWithReasonForce(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-photos'));

        $dispatcher = $this->createMock(JobDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) {
                $payload = $job->payload()->toArray();
                return $job->type() === JobType::PHOTOS
                    && $job->subjectId() === 10
                    && $payload['task_id'] === 20
                    && $payload['reason'] === 'oops'
                    && $payload['force'] === true
                    && $payload['trace_id'] === 'trace-photos';
            }))
            ->willReturn($this->job('photos'));

        $jobs = new PhotosJobs($dispatcher);
        $job = $jobs->dispatchPhotosRetry(10, 20, 'oops', true, 'cid-photos');

        $this->assertSame('photos', $job->type);
    }

    public function testExportDispatchRun(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-export'));

        $dispatcher = $this->createMock(JobDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(fn(Job $job) => $job->type() === JobType::EXPORT && $job->payload()->toArray()['export_id'] === 7))
            ->willReturn($this->job('export'));

        $jobs = new ExportJobs($dispatcher);
        $job = $jobs->dispatchExportRun(7, 'cid-export');

        $this->assertSame('export', $job->type);
    }

    public function testPublishCancelDispatches(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-publish'));

        $dispatcher = $this->createMock(JobDispatcher::class);
        $dispatcher->expects($this->once())
            ->method('enqueue')
            ->with($this->callback(function (Job $job) {
                $payload = $job->payload()->toArray();
                return $job->type() === JobType::PUBLISH
                    && $payload['task_id'] === 25
                    && $payload['reason'] === 'user_cancel'
                    && $payload['trace_id'] === 'trace-publish';
            }))
            ->willReturn($this->job('publish'));

        $jobs = new PublishJobs($dispatcher);
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
