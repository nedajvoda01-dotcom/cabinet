<?php
// backend/src/Workers/BaseWorker.php

namespace App\Workers;

use App\Queues\QueueJob;
use App\Queues\QueueService;
use App\Adapters\AdapterException;

/**
 * Базовый воркер:
 * 1) fetchNext()
 * 2) handle($job)
 * 3) success -> handleSuccess()
 * 4) error   -> handleFailure() (retry/DLQ)
 *
 * Workers импортируют Modules + Adapters, не трогают Controllers/Routes.
 */
abstract class BaseWorker
{
    public function __construct(
        protected QueueService $queues,
        protected string $workerId
    ) {}

    /** Какой тип очереди слушает воркер */
    abstract protected function queueType(): string;

    /** Основная обработка job */
    abstract protected function handle(QueueJob $job): void;

    /** Hook for subclasses to react on successful processing (WS/status). */
    protected function afterSuccess(QueueJob $job): void {}

    /**
     * Hook for subclasses to react on failure classification.
     *
     * @param array{code?:string,message?:string,meta?:array,fatal?:bool} $error
     * @param string $outcome retrying|dlq
     */
    protected function afterFailure(QueueJob $job, array $error, string $outcome): void {}

    /** Один тик воркера */
    public function tick(): void
    {
        $job = $this->queues->fetchNext($this->queueType(), $this->workerId);
        if (!$job) {
            // ничего нет — спокойно выходим
            return;
        }

        try {
            $this->handle($job);
            $this->queues->handleSuccess($job);
            $this->afterSuccess($job);
        } catch (AdapterException $e) {
            // ошибки адаптеров → retryable/fatal по флагу
            $outcome = $this->queues->handleFailure($job, $e->toErrorArray());
            $this->afterFailure($job, $e->toErrorArray(), $outcome);
        } catch (\Throwable $e) {
            // любые другие ошибки — retryable по умолчанию
            $error = [
                'code' => 'worker_exception',
                'message' => $e->getMessage(),
                'meta' => ['trace' => $e->getTraceAsString()],
                'fatal' => false,
            ];

            $outcome = $this->queues->handleFailure($job, $error);
            $this->afterFailure($job, $error, $outcome);
        }
    }
}
