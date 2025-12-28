<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

enum JobStatus: string
{
    case QUEUED = 'queued';
    case RUNNING = 'running';
    case SUCCEEDED = 'succeeded';
    case FAILED = 'failed';
    case DEAD_LETTER = 'dead_letter';
}
