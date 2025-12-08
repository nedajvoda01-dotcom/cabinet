# Admin feature (cabinet/frontend/src/features/admin)

Admin UI = поддержание здоровья конвейера.

## Screens
- Queues dashboard: depth by type, jobs list, pause/resume.
- DLQ: dead jobs list, item view, retry + bulk retry.
- Logs: latest errors/events with correlation_id.
- Integrations: health of external services.

## API (Spec)
Queues:
- GET  /admin/queues
- GET  /admin/queues/:type/jobs
- POST /admin/queues/:type/pause
- POST /admin/queues/:type/resume

DLQ:
- GET  /admin/dlq
- GET  /admin/dlq/:id
- POST /admin/dlq/:id/retry
- POST /admin/dlq/bulk-retry

System:
- GET /admin/health
- GET /admin/logs

## WS events (Spec)
- queue.depth.updated
- dlq.updated
- health.updated
