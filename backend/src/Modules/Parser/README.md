# Parser module

Домен парсинга карточек Autocontent Pipeline.

## Назначение

Parser отвечает за:
- постановку parser-задач для карточек,
- взаимодействие с внешним парсером через background jobs,
- приём результатов парсинга (webhook),
- обновление payload карточки и перевод её статуса **через StateMachine**.

## Реализованные endpoints

- `POST /parser/run`
  - создать parser task для карточки и поставить в очередь
  - body: `{ card_id, source_url?, force?, params? }`
- `GET /parser/tasks`
  - список parser задач, фильтры `status, type, card_id, from_ts, to_ts`, пагинация `limit/offset`
- `GET /parser/tasks/:id`
  - получить конкретную parser задачу
- `POST /parser/tasks/:id/retry`
  - ручной retry parser задачи `{ reason?, force? }`
- `POST /parser/webhook`
  - коллбек от внешнего parser:
    `{ task_id, card_id, status(done|failed), parsed_payload?, error_code?, error_message? }`

## Статусы parser_tasks

`queued -> running -> done|failed`

## Таблицы (дефолтные)

`ParserModel` использует:
- `parser_tasks`
- `cards` (payload_json)
- `audit_logs`

Если у вас другие таблицы/поля — правьте только `ParserModel.php`.

## Слои

- `ParserController.php` — HTTP/REST.
- `ParserService.php` — доменная логика и SM-переходы.
- `ParserModel.php` — БД/хранилища.
- `ParserSchemas.php` — DTO/валидация форматов.
- `ParserJobs.php` — постановка run/retry задач.
