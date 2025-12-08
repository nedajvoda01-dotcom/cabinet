# Publish feature (frontend/src/features/publish)

Publish — финальная стадия pipeline, которая публикует карточки во внешние каналы/площадки.
Задача модуля — создать publish-task, дождаться исполнения воркерами,
зафиксировать refs/urls результата, отдать оператору кнопки восстановления.

## Responsibilities
- Список publish-задач с фильтрами по status / card_id / channel / date range.
- Экран publish-задачи:
  - статус, attempts, ошибка,
  - входные параметры (channel, export_ref, rules),
  - прогресс по шагам (если приходит),
  - результат публикации: publish_refs/urls/ids.
- Управление стадией:
  - Start publish (создать задачу),
  - Retry publish (для failed/canceled),
  - Cancel publish (для queued/processing).
  - Unpublish (optional, если backend поддерживает).

## Publish task statuses (MVP)
- queued
- processing
- ready     (означает: публикация завершена; для карточки это `published`)
- failed
- canceled

## API
- `POST /publish/run`               create/run publish task
- `GET  /publish/tasks`            list tasks
- `GET  /publish/tasks/:id`        get task
- `POST /publish/tasks/:id/cancel` cancel task
- `POST /publish/tasks/:id/retry`  retry task
- `POST /publish/tasks/:id/unpublish` optional
- `POST /publish/webhook`          callback from workers
- `GET  /publish/metrics`          ops metrics

## WS events (optional)
- `publish.progress`
- `publish.status.updated`
- `publish.result.updated`
