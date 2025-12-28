<?php
declare(strict_types=1);

namespace Backend\Application\Contracts;

enum ErrorKind: string
{
    case TRANSIENT = 'transient';
    case PERMANENT = 'permanent';
    case AUTH = 'auth';
    case RATE_LIMIT = 'rate_limit';
    case BAD_INPUT = 'bad_input';
    case CONFLICT = 'conflict';
    case NOT_FOUND = 'not_found';
    case UNKNOWN = 'unknown';
}
