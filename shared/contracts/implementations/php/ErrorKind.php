<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

enum ErrorKind: string
{
    case VALIDATION_ERROR = 'validation_error';
    case SECURITY_DENIED = 'security_denied';
    case NOT_FOUND = 'not_found';
    case INTERNAL_ERROR = 'internal_error';
    case INTEGRATION_UNAVAILABLE = 'integration_unavailable';
    case RATE_LIMITED = 'rate_limited';
}
