<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineState;

interface PipelineStateRepository
{
    public function save(PipelineState $state): void;

    public function findByJobId(JobId $jobId): ?PipelineState;
}
