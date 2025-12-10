<?php
// tests/integration/contracts.integration.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Queues\QueueService;
use App\Queues\QueueJob;
use App\Queues\RetryPolicy;
use App\Queues\QueueRepository;
use App\Queues\DlqRepository;
use Backend\Logger\LoggerInterface;
use DateTimeImmutable;

final class ContractsIntegrationTest extends TestCase
{
    public function test_contract_mismatch_is_fatal_and_goes_to_dlq(): void
    {
        $repo = $this->createMock(QueueRepository::class);
        $dlq = $this->createMock(DlqRepository::class);
        $policy = new RetryPolicy();
        $logger = $this->createMock(LoggerInterface::class);

        $job = $this->job('parser', 'parser_payload', 10, ['correlation_id' => 'cid-1']);

        $repo->expects($this->once())->method('markDead')->with($job->id, $this->anything(), $this->arrayHasKey('code'));
        $dlq->expects($this->once())->method('put')->with($job);

        $service = new QueueService($repo, $dlq, $policy, $logger);
        $outcome = $service->handleFailure($job, [
            'code' => 'contract_mismatch',
            'message' => 'schema mismatch',
            'fatal' => true,
        ]);

        $this->assertSame('dlq', $outcome);
    }

    public function test_transient_error_is_retried(): void
    {
        $repo = $this->createMock(QueueRepository::class);
        $dlq = $this->createMock(DlqRepository::class);
        $policy = $this->createMock(RetryPolicy::class);
        $logger = $this->createMock(LoggerInterface::class);

        $job = $this->job('photos', 'card', 11, ['correlation_id' => 'cid-2']);

        $policy->expects($this->once())->method('shouldRetry')->with(1)->willReturn(true);
        $policy->expects($this->once())->method('nextRetryAt')->with(1)->willReturn(new DateTimeImmutable('2024-01-01T00:00:00Z'));

        $repo->expects($this->once())
            ->method('markRetrying')
            ->with($job->id, 1, $this->isInstanceOf(DateTimeImmutable::class), $this->arrayHasKey('message'));

        $service = new QueueService($repo, $dlq, $policy, $logger);
        $outcome = $service->handleFailure($job, [
            'code' => 'timeout',
            'message' => 'temporary',
            'fatal' => false,
        ]);

        $this->assertSame('retrying', $outcome);
    }

    private function job(string $type, string $entity, int $entityId, array $payload): QueueJob
    {
        $job = new QueueJob();
        $job->id = 1;
        $job->type = $type;
        $job->entity = $entity;
        $job->entityId = $entityId;
        $job->payload = $payload;
        $job->attempts = 0;
        return $job;
    }
}
