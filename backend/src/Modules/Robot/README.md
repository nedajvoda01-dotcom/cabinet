# Robot Module

Robot — внутренний модуль публикации карточек на Avito через Dolphin Anty.
Для backend-ядра Robot является "черным ящиком": PublishModule вызывает RobotService,
передавая данные карточки/публикации, и получает статус исполнения.

## Responsibilities
- Запуск публикации одной карточки (через Dolphin/Avito адаптеры).
- Хранение RobotRun (история попыток робота по publish_job).
- Выдача статуса выполнения (успех/ошибка/в процессе).
- Переопрос статусов (robot_status queue).
- Retry публикаций при ошибках по правилам Reliability (через DLQ/RetryPolicy).

## Public API (called by PublishModule)
- RobotService::publishCard(int $cardId, int $publishJobId, array $options = [])
- RobotService::getRunStatus(int $runId)
- RobotService::syncStatuses(array $filter = [])
- RobotService::retryRun(int $runId)

## Statuses
RobotRun.status:
- queued          -> задача создана, ждёт воркера
- processing      -> робот выполняет публикацию
- success         -> опубликовано на Avito
- failed_retry    -> ошибка, допускается retry
- failed_fatal    -> фатальная ошибка, уходит в DLQ/ручной retry
- external_wait   -> робот отдал "в работе", ждём robot_status sync

PublishModule маппит эти статусы на pipeline карточки:
- success        => card.status = published
- failed_*       => card.status = publish_failed (и/или DLQ)
- external_wait  => card.status = publishing

## Tables
- robot_runs
  - id
  - card_id
  - publish_job_id
  - idempotency_key
  - status
  - attempt
  - payload_json
  - external_ref_json (refs from Dolphin/Avito)
  - last_error_json
  - created_at, updated_at

## Notes
Интеграции сделаны через интерфейсы-адаптеры:
- DolphinAdapterInterface
- AvitoAdapterInterface
RobotService не знает деталей реализации адаптеров.
