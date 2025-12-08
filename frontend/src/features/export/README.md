# Export feature (frontend/src/features/export)

Export — стадия pipeline, которая формирует выгрузку по карточке/пакету карточек
в нужном формате (CSV/XLSX/JSON) и отдаёт ссылку на файл.

## Responsibilities
- Список export-задач с фильтрами по status / card_id / date range.
- Экран export-задачи:
  - статус, attempts, ошибка,
  - параметры выгрузки,
  - ссылки на download.
- Управление стадией:
  - Start export (создать задачу),
  - Retry export (для failed/canceled),
  - Cancel export (для queued/processing),
  - Download (для ready).

UI используется в Operator контуре и Admin контуре.

## Export task statuses (MVP)
- queued
- processing
- ready
- failed
- canceled

## API
- `POST /export`                    create export task
- `GET  /export`                    list tasks
- `GET  /export/:id`                get task
- `POST /export/:id/cancel`         cancel
- `POST /export/:id/retry`          retry
- `GET  /export/:id/download`       download file (returns blob)

## WS events (optional)
- `export.progress`
- `export.status.updated`
