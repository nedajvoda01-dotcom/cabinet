# Admin feature (cabinet/frontend/src/features/admin)

Административный контур Autocontent.  
Назначение — **наблюдаемость и управление здоровьем pipeline**, а не работа с карточками.  
Admin UI должен быстро показывать узкие места, падения, рост DLQ и давать безопасные рычаги восстановления.  
См. Part 17: Admin UI — ключевые экраны и логика.

## Страницы (Admin app)
- `/admin/dashboard`
  - KPI pipeline: throughput, retries%, DLQ growth
  - глубина очередей по доменам
  - health-индикаторы интеграций
  - топ причин ошибок за сутки
- `/admin/queues`
  - список очередей (photos/export/publish/parser/status)
  - depth, rate, avg latency
  - фильтры queued/retrying/processing
  - pause/resume очередей
- `/admin/dlq`
  - фатальные задачи, причина, payload, attempts, source
  - Retry / Bulk retry / Drop
  - ссылка на затронутые Cards
- `/admin/logs`
  - поиск по audit_logs + system_logs
  - фильтры: user, card_id, action, time range
  - корреляция по correlation_id
- `/admin/integrations` (health)
  - статус/латентность сервисов
  - test call + раскрытие ошибок

## API
Фича использует backend-эндпоинты:
- `GET /admin/dashboard`
- `GET /admin/queues`
- `POST /admin/queues/{type}/pause`
- `POST /admin/queues/{type}/resume`
- `GET /admin/dlq`
- `POST /admin/dlq/retry`
- `POST /admin/dlq/retry-bulk`
- `POST /admin/dlq/drop`
- `GET /admin/logs`
- `GET /admin/health`
- `POST /admin/integrations/{service}/test`

Реализовано в `api.ts` и типизировано через `schemas.ts`.

## WS события
Admin UI может получать live-обновления:
- `queue.depth.updated` → обновить список очередей
- `dlq.updated` → обновить DLQ/дашборд
- `health.updated` → обновить integrations/health виджет

## Экспорт
Баррель `index.ts` экспортирует:
- типы/схемы/клиент API
- Admin UI React-компоненты
