# Admin module

Административный домен (Spec v1).

## Назначение

Admin — системный контур для наблюдаемости и управления pipeline:
- контроль очередей и задач,
- DLQ/ретраи,
- health системы,
- аудит/логи,
- управление пользователями и ролями.

## Реализованные endpoints (по Spec)

### Queues
- `GET /admin/queues`
  - список очередей (depth, in_flight, retrying, paused)
- `GET /admin/queues/:type/jobs`
  - задачи конкретной очереди
- `POST /admin/queues/:type/pause`
  - поставить очередь на паузу
- `POST /admin/queues/:type/resume`
  - снять паузу

### DLQ
- `GET /admin/dlq`
  - список задач в DLQ
- `GET /admin/dlq/:id`
  - конкретная DLQ-задача
- `POST /admin/dlq/:id/retry`
  - ручной retry одной DLQ-задачи
- `POST /admin/dlq/bulk-retry`
  - массовый retry (опционально по type и limit)

### System
- `GET /admin/health`
  - health-сводка системы (минимум: БД)
- `GET /admin/logs`
  - объединённый стрим audit + system logs с фильтрами

### Users / Roles
- `GET /admin/users`
  - список пользователей с поиском/фильтрами
- `POST /admin/users/:id/roles`
  - полная замена ролей пользователя

## Слои

- `AdminController.php` — HTTP/REST.
- `AdminService.php` — бизнес оркестрация.
- `AdminModel.php` — доступ к данным и интеграциям.
- `AdminSchemas.php` — DTO/форматная валидация.
- `AdminJobs.php` — постановка retry-задач.

## Зависимости и точки адаптации

`AdminModel` использует дефолтные таблицы:
- `queues`, `jobs`, `audit_logs`, `system_logs`, `users`, `users_roles`.
Если у вас другие названия/поля — правьте **только AdminModel**.

Очередь/шина воркеров подключается в `AdminJobs`
(методы `dispatchDlqRetry`, `dispatchDlqBulkRetry`).
