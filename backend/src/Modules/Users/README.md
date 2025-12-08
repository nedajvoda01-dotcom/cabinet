# Users module

Домейн пользователей и ролей (RBAC) для Autocontent.

## Responsibilities
- Профиль текущего пользователя (`me`).
- CRUD пользователей (admin/owner).
- Назначение/снятие ролей (admin/owner).
- Блокировка/разблокировка аккаунта:
  - блокировка запрещает login и любые новые действия в системе,
  - история действий не удаляется.

## Data model (MVP)
См. Autocontent Spec:
- `users`: id, email(UQ), name, password_hash, is_active, created_at, updated_at.
- `roles`: id, code(UQ), title, permissions_json, created_at.
- `users_roles`: user_id, role_id, assigned_at. 【см. Spec】

## Public HTTP API
Группа `Auth / Users`:
- `GET /users/me`
- `GET /users`
- `GET /users/:id`
- `POST /users`
- `PATCH /users/:id`
- `DELETE /users/:id`
- `POST /users/:id/roles/assign`
- `POST /users/:id/roles/revoke`
- `POST /users/:id/block`
- `POST /users/:id/unblock`

## Permissions (RBAC)
- users.read
- users.create
- users.update
- users.delete
- roles.assign
Owner/Superadmin имеет *all permissions*. 【см. Spec】

Проверки прав происходят в middleware/guard,
UsersService предполагает что guard уже пропустил запрос.
