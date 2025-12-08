# Export module

Домен экспорта данных из Autocontent Pipeline.

## Назначение

Export отвечает за:
- постановку export задач по фильтрам/параметрам,
- наблюдение за прогрессом,
- выдачу результата пользователю (download_url),
- отмену и ручной retry.

## Реализованные endpoints

- `POST /export`
  - создать экспорт: `{ type, params?, format? }`
  - статус сразу `queued`, background job запускается через `ExportJobs::dispatchExportRun`
- `GET /export`
  - список экспортов с фильтрами `type, status, user_id, from_ts, to_ts`, пагинация `limit/offset`
- `GET /export/:id`
  - получить экспорт
- `POST /export/:id/cancel`
  - отменить экспорт (если не завершён)
- `POST /export/:id/retry`
  - ручной retry (если не running; done — только force)
- `GET /export/:id/download`
  - информация для скачивания (`file_path` / `download_url`) — только когда status=done

## Статусы экспортов

`queued -> running -> done|failed|canceled`

## Таблицы (дефолтные)

`ExportModel` использует:
- `exports`
- `audit_logs`

Если схема другая или используете S3/Minio signed URLs —
правьте только `ExportModel.php` (метод `setExportFile` и поля).

## Слои

- `ExportController.php` — HTTP/REST.
- `ExportService.php` — доменная логика.
- `ExportModel.php` — БД/хранилище файлов.
- `ExportSchemas.php` — DTO/валидация формата.
- `ExportJobs.php` — постановка background экспортов.
