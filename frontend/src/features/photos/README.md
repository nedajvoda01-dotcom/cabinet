# Photos feature (frontend/src/features/photos)

Photos — стадия pipeline обработки фото карточки.
Задача модуля — получить сырой набор изображений, прогнать обработку (masking/normalize/order),
и сохранить итоговые фото для дальнейшего Export/Publish.

## Responsibilities
- Список photos-задач (tasks) с фильтрами по status / card_id / date range.
- Экран photos-задачи:
  - статус, attempts, ошибка,
  - входной payload (список raw urls / rules),
  - выходной результат (masked urls + order),
  - прогресс по шагам (если приходит),
  - ссылка на карточку.
- Управление стадией:
  - Start photos (создать задачу),
  - Retry photos (для failed/canceled),
  - Cancel photos (для queued/processing).

## Photos task statuses (MVP)
- queued
- processing
- ready
- failed
- canceled

## API
- `POST /photos/run`               create/run photos task
- `GET  /photos/tasks`            list tasks
- `GET  /photos/tasks/:id`        get task
- `POST /photos/tasks/:id/retry`  retry task
- (optional) `POST /photos/tasks/:id/cancel`
- `POST /photos/webhook`          callback from workers

Фото-артефакты:
- `GET /photos/card/:card_id`          list photos of card
- `DELETE /photos/:id`                delete photo
- `POST /photos/card/:card_id/primary` set primary/order (optional)

## WS events (optional)
- `photos.progress`
- `photos.status.updated`
