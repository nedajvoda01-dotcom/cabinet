# Cards module

Домен карточек Autocontent Pipeline.

## Назначение

Cards — центральная сущность конвейера. Любые смены рабочих статусов
делаются **только через StateMachine** и фиксируются событиями.

## Реализованные endpoints

### CRUD
- `GET /cards`
  - список карточек: фильтры `q, status, user_id, locked, from_ts, to_ts`, пагинация `limit/offset`
- `GET /cards/:id`
  - получить карточку
- `POST /cards`
  - создать карточку
- `PATCH /cards/:id`
  - обновить поля карточки (кроме status)
- `DELETE /cards/:id`
  - удалить карточку

### Status transitions (StateMachine-first)
- `POST /cards/:id/transition`
  - `{ action, meta? }` → переводит статус через SM
- `POST /cards/bulk-transition`
  - `{ card_ids, action, meta? }` → массовые переходы

### Manual retry
- `POST /cards/:id/retry`
  - `{ reason?, force? }` → ставит retry job (если разрешено SM)

## Таблицы (дефолтные)

`CardsModel` использует:
- `cards`
- `card_events`
- `audit_logs`

Если схема другая — правим только `CardsModel.php`.

## Слои

- `CardsController.php` — HTTP/REST.
- `CardsService.php` — доменная логика + StateMachine.
- `CardsModel.php` — БД/хранилища.
- `CardsSchemas.php` — DTO/валидация форматов.
- `CardsJobs.php` — постановка retry/служебных задач.
