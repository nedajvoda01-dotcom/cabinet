<?php
declare(strict_types=1);

namespace Backend\Modules\Users;

use RuntimeException;

/**
 * UsersService
 *
 * Бизнес-логика:
 * - CRUD пользователей
 * - assign/revoke ролей
 * - block/unblock
 * - профиль me
 *
 * Guard (RBAC) должен проверять permissions до входа сюда.
 */
final class UsersService
{
    public function __construct(
        private UsersModel $model,
        private UsersJobs $jobs,
        private PasswordHasher $hasher
    ) {}

    public function me(int $userId): array
    {
        return $this->model->getUserById($userId);
    }

    public function list(array $dto): array
    {
        return $this->model->listUsers($dto);
    }

    public function get(int $id): array
    {
        return $this->model->getUserById($id);
    }

    public function create(array $dto, ?int $actorId): array
    {
        if ($this->model->getUserByEmail($dto['email'])) {
            throw new RuntimeException("Email already exists");
        }

        $hash = $dto['password'] ? $this->hasher->hash($dto['password']) : null;

        $user = $this->model->createUser(
            $dto['email'],
            $dto['name'],
            $hash,
            (bool)$dto['is_active']
        );

        $this->model->writeAudit($actorId, 'users.create', 'user', (int)$user['id'], [], $user);
        $this->jobs->dispatchUserCreated((int)$user['id']);

        return $user;
    }

    public function update(int $id, array $dto, ?int $actorId): array
    {
        $before = $this->model->getUserById($id);

        $patch = [];
        if (isset($dto['name'])) $patch['name'] = $dto['name'];
        if (isset($dto['is_active'])) $patch['is_active'] = (bool)$dto['is_active'];
        if (isset($dto['password'])) $patch['password_hash'] = $this->hasher->hash($dto['password']);

        $user = $this->model->updateUser($id, $patch);

        $this->model->writeAudit($actorId, 'users.update', 'user', $id, $before, $user);
        return $user;
    }

    public function delete(int $id, ?int $actorId): void
    {
        $before = $this->model->getUserById($id);

        $this->model->deleteUser($id);

        $this->model->writeAudit($actorId, 'users.delete', 'user', $id, $before, []);
    }

    public function assignRole(int $userId, array $dto, ?int $actorId): array
    {
        $before = $this->model->getUserById($userId);

        $role = $dto['role_id']
            ? $this->model->getRoleById($dto['role_id'])
            : $this->model->getRoleByCode($dto['role_code']);

        $this->model->assignRole($userId, (int)$role['id']);

        $after = $this->model->getUserById($userId);
        $this->model->writeAudit($actorId, 'roles.assign', 'user', $userId, $before, $after);

        return $after;
    }

    public function revokeRole(int $userId, array $dto, ?int $actorId): array
    {
        $before = $this->model->getUserById($userId);

        $role = $dto['role_id']
            ? $this->model->getRoleById($dto['role_id'])
            : $this->model->getRoleByCode($dto['role_code']);

        $this->model->revokeRole($userId, (int)$role['id']);

        $after = $this->model->getUserById($userId);
        $this->model->writeAudit($actorId, 'roles.revoke', 'user', $userId, $before, $after);

        return $after;
    }

    public function block(int $userId, ?int $actorId, string $reason='blocked'): array
    {
        $before = $this->model->getUserById($userId);

        $after = $this->model->updateUser($userId, ['is_active' => false]);

        $this->model->writeAudit($actorId, 'users.block', 'user', $userId, $before, $after);
        $this->jobs->dispatchUserBlocked($userId, $reason);

        return $after;
    }

    public function unblock(int $userId, ?int $actorId): array
    {
        $before = $this->model->getUserById($userId);

        $after = $this->model->updateUser($userId, ['is_active' => true]);

        $this->model->writeAudit($actorId, 'users.unblock', 'user', $userId, $before, $after);

        return $after;
    }
}

/**
 * PasswordHasher
 * В проекте может уже быть утиль — тогда подменишь инжектом.
 */
final class PasswordHasher
{
    public function hash(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    public function verify(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }
}
