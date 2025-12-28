<?php
declare(strict_types=1);

use App\Queues\QueueJob;
use App\Queues\QueueService;
use Backend\Application\Contracts\Error;
use Backend\Application\Contracts\ErrorKind;
use Backend\Application\Pipeline\Dlq\InMemoryDlqWriter;
use Backend\Application\Pipeline\Idempotency\InMemoryIdempotencyStore;
use Backend\Application\Pipeline\Reliability\ReliabilityHandler;
use Backend\Application\Pipeline\Retry\ErrorClassifier;
use Backend\Application\Pipeline\Retry\RetryPolicy;
use PHPUnit\Framework\TestCase;

final class PipelineReliabilityTest extends TestCase
{
    public function testRetryPolicyBackoffAndKinds(): void
    {
        $policy = new RetryPolicy(new ErrorClassifier(), 2, [10, 20]);
        $now = time();
        $transient = Error::fromMessage('timeout', ErrorKind::TRANSIENT, 'retryable');
        $badInput = Error::fromMessage('bad_input', ErrorKind::BAD_INPUT, 'invalid');

        $this->assertTrue($policy->classifyAndDecide($transient, 0));
        $this->assertFalse($policy->classifyAndDecide($badInput, 0));
        $this->assertSame(date('Y-m-d H:i:s', $now + 10), $policy->nextRetryAt(1, $now));
        $this->assertSame(date('Y-m-d H:i:s', $now + 20), $policy->nextRetryAt(2, $now));
    }

    public function testPermanentFailureGoesToDlqWithAttempts(): void
    {
        $queues = new class extends QueueService {
            public array $failures = [];
            public function __construct() {}
            public function handleSuccess(QueueJob $job): void {}
            public function handleFailureWithDecision(QueueJob $job, array $error, bool $shouldRetry, ?string $nextRetryAt = null): string
            {
                $this->failures[] = ['error' => $error, 'retry' => $shouldRetry, 'next' => $nextRetryAt, 'attempts' => $job->attempts + 1];
                return $shouldRetry ? 'retrying' : 'dlq';
            }
        };

        $dlq = new InMemoryDlqWriter();
        $handler = new ReliabilityHandler(new RetryPolicy(new ErrorClassifier(), 1), $dlq, new InMemoryIdempotencyStore(), $queues, true);

        $job = new QueueJob();
        $job->id = 1;
        $job->type = 'publish';
        $job->entity = 'card';
        $job->entityId = 7;
        $job->payload = ['idempotency_key' => 'k1', 'trace_id' => 'trace-1'];
        $job->attempts = 0;

        $handler->process(
            $job,
            fn () => throw new \RuntimeException('fatal path'),
            fn () => null,
            fn () => null,
        );

        $this->assertSame('dlq', $queues->failures[0]['retry'] ? 'retrying' : 'dlq');
        $this->assertCount(1, $dlq->records);
        $this->assertSame(1, $dlq->records[0]->attempts);
        $this->assertSame('trace-1', $dlq->records[0]->job->traceId());
    }

    public function testIdempotencyPreventsDuplicateEffectfulExecution(): void
    {
        $queues = new class extends QueueService {
            public array $success = [];
            public function __construct() {}
            public function handleSuccess(QueueJob $job): void { $this->success[] = $job->id; }
            public function handleFailureWithDecision(QueueJob $job, array $error, bool $shouldRetry, ?string $nextRetryAt = null): string { return 'retrying'; }
        };

        $dlq = new InMemoryDlqWriter();
        $idempotency = new InMemoryIdempotencyStore();
        $handler = new ReliabilityHandler(new RetryPolicy(new ErrorClassifier(), 2), $dlq, $idempotency, $queues, true);

        $job = new QueueJob();
        $job->id = 3;
        $job->type = 'publish';
        $job->entity = 'card';
        $job->entityId = 9;
        $job->payload = ['idempotency_key' => 'dup-key', 'trace_id' => 'trace-dup'];
        $job->attempts = 0;

        $executions = 0;
        $callback = function () use (&$executions): void {
            $executions++;
        };

        $handler->process($job, $callback, fn () => null, fn () => null);
        $handler->process($job, $callback, fn () => null, fn () => null);

        $this->assertSame(1, $executions);
        $this->assertCount(2, $queues->success);
    }
}
