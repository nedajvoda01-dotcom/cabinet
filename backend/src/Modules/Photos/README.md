# Photos module

Домен фото-конвейера Autocontent Pipeline.

## Назначение

Photos отвечает за:
- постановку фото-задач (генерация/обработка/загрузка) для карточек,
- взаимодействие с внешним фото-сервисом через jobs,
- приём результатов (webhook),
- сохранение фото-артефактов и синхронизацию их в payload карточки,
- перевод статуса карточки **только через StateMachine**.

## Реализованные endpoints

### Photo tasks
- `POST /photos/run`
  - создать photo_task и поставить в очередь  
  - body: `{ card_id, mode?, source_urls?, force?, params? }`
- `GET /photos/tasks`
  - список задач, фильтры `status, mode, card_id, from_ts, to_ts`, пагинация `limit/offset`
- `GET /photos/tasks/:id`
  - получить задачу
- `POST /photos/tasks/:id/retry`
  - ручной retry `{ reason?, force? }`
- `POST /photos/webhook`
  - callback от внешнего сервиса:  
    `{ task_id, card_id, status(done|failed), photos?, error_code?, error_message? }`

### Photo artifacts
- `GET /photos/card/:card_id`
  - список фото карточки
- `DELETE /photos/:id`
  - удалить фото
- `POST /photos/card/:card_id/primary`
  - сделать фото главным: `{ photo_id }`

## Статусы photo_tasks

`queued -> running -> done|failed`

## Таблицы (дефолтные)

`PhotosModel` использует:
- `photo_tasks`
- `photos`
- `cards` (payload_json)
- `audit_logs`

Если схема другая — правьте только `PhotosModel.php`.

## Слои

- `PhotosController.php` — HTTP/REST.
- `PhotosService.php` — доменная логика + SM переходы.
- `PhotosModel.php` — БД/хранилища.
- `PhotosSchemas.php` — DTO/валидация формата.
- `PhotosJobs.php` — постановка run/retry задач.
