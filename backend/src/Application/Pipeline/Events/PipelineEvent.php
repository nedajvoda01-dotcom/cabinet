<?php
declare(strict_types=1);

namespace Backend\Application\Pipeline\Events;

use Backend\Application\Pipeline\Jobs\Job;

final class PipelineEvent
{
    public function __construct(public string $name, public Job $job)
    {
    }
}
