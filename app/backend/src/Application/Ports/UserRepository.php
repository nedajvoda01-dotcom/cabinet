<?php

declare(strict_types=1);

namespace Cabinet\Backend\Application\Ports;

use Cabinet\Backend\Domain\Users\User;
use Cabinet\Backend\Domain\Users\UserId;

interface UserRepository
{
    public function save(User $user): void;

    public function findById(UserId $id): ?User;
}
