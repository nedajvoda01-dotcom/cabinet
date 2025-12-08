# Publish module

Домен публикации карточек на внешние площадки (Avito/Dolphin/…).

## Назначение

Publish отвечает за:
- постановку publish-задач для карточек,
- общение с внешними площадками через jobs/adapters,
- приём результатов по webhook,
- запись результата в payload карточки,
- перевод статусов карточек **через StateMachine**,
- метрики публикации для Admin Monitor.

## Реализованные endpoints

### Publish tasks
- `POST /publish/run`
  - создать publish_task и поставить в очередь  
  - body: `{ card_id, platform, account_id?, force?, params? }`
- `GET /publish/tasks`
  - список задач, фильтры `status, platform, account_id, card_id, from_ts, to_ts`, пагинация `limit/offset`
- `GET /publish/tasks/:id`
  - получить задачу
- `POST /publish/tasks/:id/cancel`
  - отменить задачу `{ reason? }`
- `POST /publish/tasks/:id/retry`
  - ручной retry `{ reason?, force? }`
- `POST /publish/webhook`
  - callback от площадки/сервиса:
    `{ task_id, card_id, status(done|failed|blocked), external_id?, external_url?, error_code?, error_message? }`

### Metrics
- `GET /publish/metrics`
  - агрегация publish_tasks по времени/статусам
  - query: `from_ts, to_ts, platform?, account_id?, bucket_sec?`

## Статусы publish_tasks

`queued -> running -> done|failed|blocked|canceled`

## Таблицы (дефолтные)

`PublishModel` использует:
- `publish_tasks`
- `cards` (payload_json.publish)
- `audit_logs`

Если схема другая — правьте только `PublishModel.php`.

## Слои

- `PublishController.php` — HTTP/REST.
- `PublishService.php` — доменная логика + SM-переходы.
- `PublishModel.php` — БД/хранилища/агрегации.
- `PublishSchemas.php` — DTO/валидация форматов.
- `PublishJobs.php` — постановка run/retry/cancel задач.
