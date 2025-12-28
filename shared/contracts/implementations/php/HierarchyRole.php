<?php

declare(strict_types=1);

namespace Cabinet\Contracts;

enum HierarchyRole: string
{
    case USER = 'user';
    case ADMIN = 'admin';
    case SUPER_ADMIN = 'super_admin';
}
