<?php
declare(strict_types=1);

namespace Backend\Application\Contracts;

enum Status: string
{
    case OK = 'ok';
    case PENDING = 'pending';
    case RUNNING = 'running';
    case RETRY = 'retry';
    case FAILED = 'failed';
}
