<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Dlq;

use Backend\Application\Contracts\Error;
use Backend\Application\Pipeline\Jobs\Job;

final class DlqRecord
{
    public function __construct(
        public Job $job,
        public Error $error,
        public int $attempts,
        public \DateTimeImmutable $failedAt,
    ) {
    }
}

