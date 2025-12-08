# Parser feature (frontend/src/features/parser)

Parser — стадия нормализации/обогащения карточек на входе pipeline.
Задача Parser: взять сырой источник (текст/HTML/структурный input) и сформировать
нормализованный snapshot карточки, который дальше идёт в Photos/Export/Publish.

## Responsibilities
- Список parser-задач (tasks) с фильтрами по status / card_id / source / date range.
- Экран parser-задачи:
  - статус, attempts, ошибка,
  - входной payload,
  - выходной результат (card_snapshot / extracted fields),
  - ссылка на карточку.
- Управление:
  - Start parser (создать задачу),
  - Retry parser (для failed/canceled),
  - Cancel parser (для queued/processing).

## Parser task statuses (MVP)
- queued
- processing
- ready
- failed
- canceled

## API
- `POST /parser/run`               create/run parser task
- `GET  /parser/tasks`            list tasks
- `GET  /parser/tasks/:id`        get task
- `POST /parser/tasks/:id/retry`  retry task
- (optional) `POST /parser/tasks/:id/cancel` if backend supports
- `POST /parser/webhook`          callback from external parser workers

## WS events (optional)
- `parser.progress`
- `parser.status.updated`
