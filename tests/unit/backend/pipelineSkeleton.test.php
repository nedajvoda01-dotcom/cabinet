<?php
declare(strict_types=1);

use Backend\Application\Contracts\Error;
use Backend\Application\Contracts\ErrorKind;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\JobDispatcher;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Jobs\JobType;
use Backend\Application\Pipeline\Retry\ErrorClassifier;
use Backend\Application\Pipeline\Retry\RetryPolicy;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use PHPUnit\Framework\TestCase;

final class PipelineSkeletonTest extends TestCase
{
    public function testJobSeedsTraceAndIdempotency(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-123'));

        $job = Job::create(JobType::EXPORT, 'export', 42, ['action' => 'export.run']);

        $payload = $job->payload()->toArray();
        $this->assertSame('trace-123', $job->traceId());
        $this->assertSame('trace-123', $payload['trace_id']);
        $this->assertNotEmpty($job->idempotencyKey());
        $this->assertSame($job->idempotencyKey(), $payload['idempotency_key']);
    }

    public function testRetryPolicyUsesErrorKind(): void
    {
        $policy = new RetryPolicy(new ErrorClassifier(), 2);

        $transient = Error::fromMessage('timeout', ErrorKind::TRANSIENT, 'temporary');
        $permanent = Error::fromMessage('bad_input', ErrorKind::BAD_INPUT, 'nope');

        $this->assertTrue($policy->classifyAndDecide($transient, 0));
        $this->assertFalse($policy->classifyAndDecide($permanent, 0));
        $this->assertFalse($policy->shouldRetry(3, ErrorKind::TRANSIENT));
    }

    public function testPilotModuleEnqueuesThroughPipeline(): void
    {
        TraceContext::setCurrent(TraceContext::fromString('trace-export'));
        $queues = new class extends QueueService {
            public array $lastPayload = [];
            public function __construct() {}
            public function enqueueExport(int $exportId, array $payload = []): QueueJob
            {
                $job = new QueueJob();
                $job->id = 1;
                $job->type = 'export';
                $job->entity = 'export';
                $job->entityId = $exportId;
                $job->payload = $payload;

                $this->lastPayload = $payload;

                return $job;
            }
        };

        $dispatcher = new JobDispatcher($queues);
        $jobs = new \Backend\Modules\Export\ExportJobs($dispatcher);

        $job = $jobs->dispatchExportRun(55);

        $this->assertSame('export', $job->type);
        $this->assertSame(55, $job->entityId);
        $this->assertSame('trace-export', $job->payload['trace_id']);
        $this->assertArrayHasKey('idempotency_key', $job->payload);
        $this->assertSame('export.run', $job->payload['action']);
    }
}
