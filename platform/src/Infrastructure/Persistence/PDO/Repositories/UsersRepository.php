<?php

declare(strict_types=1);

namespace Cabinet\Backend\Infrastructure\Persistence\PDO\Repositories;

use Cabinet\Backend\Application\Ports\UserRepository;
use Cabinet\Backend\Domain\Shared\ValueObject\HierarchyRole;
use Cabinet\Backend\Domain\Shared\ValueObject\Scope;
use Cabinet\Backend\Domain\Shared\ValueObject\ScopeSet;
use Cabinet\Backend\Domain\Users\User;
use Cabinet\Backend\Domain\Users\UserId;
use PDO;

final class UsersRepository implements UserRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        $this->pdo = $pdo;
    }

    public function save(User $user): void
    {
        $sql = <<<SQL
        INSERT INTO users (id, role, scopes_json, is_active, created_at, updated_at)
        VALUES (:id, :role, :scopes_json, :is_active, :created_at, :updated_at)
        ON CONFLICT(id) DO UPDATE SET
            role = :role,
            scopes_json = :scopes_json,
            is_active = :is_active,
            updated_at = :updated_at
        SQL;

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([
            ':id' => $user->id()->toString(),
            ':role' => $user->role()->toString(),
            ':scopes_json' => json_encode($user->scopes()->toArray()),
            ':is_active' => $user->isActive() ? 1 : 0,
            ':created_at' => date('Y-m-d H:i:s', $user->createdAt()),
            ':updated_at' => date('Y-m-d H:i:s', $user->updatedAt()),
        ]);
    }

    public function findById(UserId $id): ?User
    {
        $sql = 'SELECT * FROM users WHERE id = :id';
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute([':id' => $id->toString()]);
        
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($row === false) {
            return null;
        }

        return $this->hydrateUser($row);
    }

    /**
     * @param array<string, mixed> $row
     */
    private function hydrateUser(array $row): User
    {
        $scopesArray = json_decode($row['scopes_json'], true);
        $scopes = array_map(fn($s) => Scope::fromString($s), $scopesArray);
        
        return User::create(
            UserId::fromString($row['id']),
            HierarchyRole::fromString($row['role']),
            ScopeSet::fromScopes($scopes),
            strtotime($row['created_at'])
        );
    }
}
