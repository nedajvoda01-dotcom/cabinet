<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\InMemory;

use Cabinet\Backend\Application\Ports\UserRepository;
use Cabinet\Backend\Domain\Users\User;
use Cabinet\Backend\Domain\Users\UserId;

final class InMemoryUserRepository implements UserRepository
{
    /** @var array<string, User> */
    private array $users = [];

    public function save(User $user): void
    {
        $this->users[$user->id()->toString()] = $user;
    }

    public function findById(UserId $id): ?User
    {
        return $this->users[$id->toString()] ?? null;
    }
}
