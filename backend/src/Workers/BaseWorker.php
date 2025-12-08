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
        } catch (AdapterException $e) {
            // ошибки адаптеров → retryable/fatal по флагу
            $this->queues->handleFailure($job, $e->toErrorArray());
        } catch (\Throwable $e) {
            // любые другие ошибки — retryable по умолчанию
            $this->queues->handleFailure($job, [
                'code' => 'worker_exception',
                'message' => $e->getMessage(),
                'meta' => ['trace' => $e->getTraceAsString()],
                'fatal' => false,
            ]);
        }
    }
}
