<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Reliability;

use App\Adapters\AdapterException;
use App\Queues\QueueJob;
use App\Queues\QueueService;
use Backend\Application\Contracts\Error;
use Backend\Application\Contracts\ErrorKind;
use Backend\Application\Contracts\TraceContext;
use Backend\Application\Pipeline\Dlq\DlqRecord;
use Backend\Application\Pipeline\Dlq\DlqWriterInterface;
use Backend\Application\Pipeline\Idempotency\IdempotencyStoreInterface;
use Backend\Application\Pipeline\Jobs\Job;
use Backend\Application\Pipeline\Retry\RetryPolicy;

final class ReliabilityHandler
{
    public function __construct(
        private RetryPolicy $policy,
        private DlqWriterInterface $dlqWriter,
        private IdempotencyStoreInterface $idempotency,
        private QueueService $queues,
        private bool $effectful = true,
    ) {
    }

    /**
     * @param callable(QueueJob):void $handle
     * @param callable():void $afterSuccess
     * @param callable(array,string):void $afterFailure
     */
    public function process(QueueJob $queueJob, callable $handle, callable $afterSuccess, callable $afterFailure): void
    {
        $job = Job::create(
            $queueJob->type,
            $queueJob->entity,
            $queueJob->entityId,
            $queueJob->payload,
            $queueJob->payload['idempotency_key'] ?? null,
            $queueJob->payload['trace_id'] ?? null,
            $queueJob->attempts,
        );

        TraceContext::setCurrent(TraceContext::fromString($job->traceId()));

        if ($this->effectful && !$this->idempotency->acquire($job)) {
            $this->queues->handleSuccess($queueJob);
            $afterSuccess();
            return;
        }

        try {
            $handle($queueJob);
            $this->queues->handleSuccess($queueJob);
            if ($this->effectful) {
                $this->idempotency->commit($job);
            }
            $afterSuccess();
        } catch (\Throwable $e) {
            $error = $this->normalizeError($e);
            $shouldRetry = $this->policy->classifyAndDecide($error, $queueJob->attempts);
            $nextRetryAt = $this->policy->nextRetryAt($queueJob->attempts + 1);
            $outcome = $this->queues->handleFailureWithDecision($queueJob, $this->errorPayload($error, $shouldRetry), $shouldRetry, $nextRetryAt);

            if (!$shouldRetry) {
                $record = new DlqRecord($job, $error, $queueJob->attempts + 1, new \DateTimeImmutable());
                $this->dlqWriter->write($record);
            }

            if ($this->effectful) {
                $this->idempotency->release($job);
            }

            $afterFailure($this->errorPayload($error, $shouldRetry), $outcome);
        }
    }

    private function normalizeError(\Throwable $e): Error
    {
        if ($e instanceof AdapterException) {
            $kind = $e->retryable ? ErrorKind::TRANSIENT : ErrorKind::PERMANENT;
            return Error::fromMessage($e->codeStr ?? 'adapter_error', $kind, $e->getMessage(), $e->meta, TraceContext::ensure());
        }

        return Error::fromThrowable($e, TraceContext::ensure());
    }

    /**
     * @return array{code:string,kind:string,message:string,details:array,traceId:string,fatal?:bool}
     */
    private function errorPayload(Error $error, bool $retryable): array
    {
        $payload = $error->toArray();
        $payload['fatal'] = !$retryable;

        return $payload;
    }
}
