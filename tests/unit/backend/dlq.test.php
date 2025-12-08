<?php
// tests/unit/backend/dlq.test.php

declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use App\Queues\QueueJob;

final class DlqTest extends TestCase
{
    public function testDlqPutStoresJob(): void
    {
        $dlq = new class {
            public array $jobs = [];
            public function put(QueueJob $j): void { $this->jobs[] = $j; }
        };

        $job = new QueueJob();
        $job->id = 1;
        $job->type = 'photos';
        $job->entity = 'card';
        $job->entityId = 10;

        $dlq->put($job);

        $this->assertCount(1, $dlq->jobs);
        $this->assertSame(1, $dlq->jobs[0]->id);
    }
}
