<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

enum ActorType: string
{
    case USER = 'user';
    case INTEGRATION = 'integration';
}
