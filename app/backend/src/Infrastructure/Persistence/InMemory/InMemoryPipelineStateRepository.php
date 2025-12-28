<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\InMemory;

use Cabinet\Backend\Application\Ports\PipelineStateRepository;
use Cabinet\Backend\Domain\Pipeline\JobId;
use Cabinet\Backend\Domain\Pipeline\PipelineState;

final class InMemoryPipelineStateRepository implements PipelineStateRepository
{
    /** @var array<string, PipelineState> */
    private array $states = [];

    public function save(PipelineState $state): void
    {
        $this->states[$state->jobId()->toString()] = $state;
    }

    public function findByJobId(JobId $jobId): ?PipelineState
    {
        return $this->states[$jobId->toString()] ?? null;
    }
}
