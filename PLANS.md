# PLANS.md — Autocontent Layered Full Implementation Roadmap (Spec‑Max)

Этот план заменяет старый “Work Plan”.  
Он задаёт **слоистую реализацию идеальной архитектуры**, где ядро не меняется при смене интеграций.  
Работа идёт **строго слоями**. Каждый слой = отдельный PR. После PR — стоп.

**Source of truth поведения:** `cabinet/AUTOCONTENT_SPEC.md`  
**Границы репозитория и слоёв:** `cabinet/AGENTS.md`  
**Инструкция для агента:** `cabinet/CODEX_INSTRUCTIONS.md`

---

## Как агенту работать по этому файлу

1) Агент читает этот план, Spec и границы.  
2) Агент выполняет **следующий невыполненный слой целиком**.  
3) Делает PR с title, указанным в слое.  
4) Останавливается.

**Запрещено:**  
- делать следующий слой в том же PR  
- менять top‑level структуру репо  
- добавлять бизнес‑логику в Adapters  
- нарушать границы импорта из `AGENTS.md`  
- “частично слой сделал — пошёл дальше”

**Если conflict Spec ↔ код ↔ план**:  
описать blockers в PR и остановиться.

---

## Слой 0 — Spec как Source of Truth (SoT)

**Цель:** поведение системы однозначно задано Spec.

**Сделать:**
- `cabinet/AUTOCONTENT_SPEC.md` в main (истина поведения)
- (опционально) `cabinet/AutocontentSpec.htm` как оригинал
- в `cabinet/AGENTS.md` есть явная ссылка на Spec.md

**DoD:**
- Spec.md лежит в main
- AGENTS.md с ссылкой на Spec.md

**PR title:** `layer0: spec as source of truth`

---

## Слой 1 — Порты (Interfaces) интеграций + нейтральный нейминг реализаций

**Цель:** Modules/Services не знают конкретных интеграторов.

### 1.1 Порты (создать)

Путь: `cabinet/backend/src/Adapters/Ports/`

**StorageAdapterInterface.php**
- putObject(string $key, string $binary, string $contentType): void
- publicUrl(string $key): string
- deleteObject(string $key): void
- exists(string $key): bool

**ParserAdapterInterface.php**
- normalizePush(array $push): array
- poll(int $limit=20): array
- ack(string $externalId, array $meta=[]): void
- downloadBinary(string $url): string
- guessExt(string $url): ?string
- uploadRaw(string $key, string $binary, string $extension): string

**PhotoProcessorAdapterInterface.php**
- submitRawBatch(int $cardId, array $rawPhotos, array $meta=[]): array
- getTask(string $taskId): array
- cancelTask(string $taskId): void

**MarketplaceAdapterInterface.php** (Dolphin)
- createProfile(array $payload): array
- startSession(array $payload): array
- stopSession(string $sessionId): void
- health(): array

**RobotAdapterInterface.php**
- runPublish(array $payload): array
- getRun(string $runId): array
- cancelRun(string $runId): void
- health(): array

> Минимальный набор методов указан по текущей архитектуре и Spec.  
> Если в коде есть доп. методы — **добавлять в порт** только если реально используются сервисами/воркерами.

### 1.2 Реализации (обновить)

- Все real‑адаптеры `implements` нужный порт.
- Переименовать исторические реализации, где имя == технология:
  - `cabinet/backend/src/Adapters/S3Adapter.php` → `S3StorageAdapter.php`
  - импорты/DI поправить везде.

### 1.3 Services используют только порты

В: `cabinet/backend/src/Modules/*/*Service.php`  
Заменить type‑hint конкретных адаптеров на интерфейсы.

### 1.4 DI(Container) бинды портов

В: `cabinet/backend/src/Server/Container.php`  
Интерфейс → реализация.

### 1.5 Тесты слоя

- Unit‑тест что контейнер отдаёт интерфейсы и сервисы собираются:
  - `cabinet/tests/unit/backend/containerPorts.test.php`

**DoD слоя 1:**
- сервисы/модули не type‑hint’ят конкретные адаптеры
- все адаптеры реализуют порты
- Container отдаёт порты
- тесты зелёные

**PR title:** `layer1: ports and neutral adapters`

---

## Слой 2 — Fail‑fast контрактная валидация в адаптерах

**Цель:** несовпадение контракта ловится на границе и идёт в fatal.

### 2.1 Валидатор

Создать: `cabinet/backend/src/Utils/ContractValidator.php`  
Метод:
- validate(array $data, string $schemaPath): void (throws ContractException)

Схемы: `external/*/contracts/*.json`

### 2.2 Валидация request/response

В каждом real‑adapter:
- validateRequest(payload, schema) перед отправкой
- validateResponse(json, schema) после ответа
- mismatch ⇒ `AdapterException(code="contract_mismatch", fatal=true)`

**Fatal=true только для:**
- schema mismatch
- 4xx с нарушением контракта/формата

**Retryable (fatal=false) для:**
- network/timeouts
- 5xx и временных сбоев

### 2.3 Тесты слоя

- fatal ошибки → DLQ
- retryable → retry

Файлы:
- `cabinet/tests/unit/backend/*Worker*.test.php`
- `cabinet/tests/integration/contracts.integration.test.php` (или per‑adapter)

**DoD слоя 2:**
- request+response валидируются во всех real‑адаптерах
- mismatch кидает fatal
- DLQ/Retry ветки покрыты тестами

**PR title:** `layer2: adapters fail-fast contracts`

---

## Слой 3 — Consumer contract tests

**Цель:** схемы становятся “законом ядра”.

### 3.1 Структура

`cabinet/tests/contracts/consumer/`

### 3.2 Тесты

Для каждого external сервиса (Parser/Photo/Storage/Dolphin/Robot):
- fixture ответа → validate схемой
- mapping/normalize адаптера → DTO/array
- assert критических полей Spec

**DoD слоя 3:**
- consumer tests есть на все порты
- CI падает при несовпадении fixture↔schema↔mapping

**PR title:** `layer3: consumer contract tests`

---

## Слой 4 — Fake adapters (local/CI)

**Цель:** интеграционные флоу гоняются без внешних сервисов.

### 4.1 Фейки

Создать в: `cabinet/backend/src/Adapters/Fakes/`
- FakeParserAdapter.php
- FakePhotoProcessorAdapter.php
- FakeStorageAdapter.php
- FakeMarketplaceAdapter.php
- FakeRobotAdapter.php

Фейки:
- читают fixtures
- возвращают валидные DTO/arrays по схемам

### 4.2 Переключение режима

`.env`: `INTEGRATIONS_MODE=fake|real`  
Container:
- fake ⇒ биндим Fake*
- real ⇒ биндим Real*

**DoD слоя 4:**
- fake‑режим позволяет пройти full‑flow
- integration/e2e работают без внешки

**PR title:** `layer4: fake adapters switchable`

---

## Слой 5 — Unified retry/timeout/idempotency

**Цель:** одинаковое поведение при сбоях для всех интеграций.

### 5.1 Retry/timeout только в двух местах

- `cabinet/backend/src/Adapters/HttpClient.php`
- `cabinet/backend/src/Queues/RetryPolicy.php`

В адаптерах локальных retry быть не должно.

### 5.2 Idempotency keys

Для повторяемых внешних вызовов:
- key = `correlation_id + entity_id + stage + intent`

Хранение факта “уже обработали” — в DB/QueueRepository или отдельном репозитории.

### 5.3 Тесты поведения

- transient → retry → ok
- permanent → DLQ
- duplicate → no double‑processing

**DoD слоя 5:**
- единые ретраи/таймауты
- идемпотентность есть и покрыта тестами

**PR title:** `layer5: unified retry and idempotency`

---

## Слой 6 — Drivers/feature flags переключения интеграций

**Цель:** замена интегратора одной строкой config.

### 6.1 Drivers

В `cabinet/backend/src/Config/config.php`:
- storage_driver = s3|local|minio
- photo_driver = photo-api|internal
- parser_driver = external|internal
- marketplace_driver = dolphin|other
- robot_driver = internal|other

### 6.2 DI выбирает реализацию

Container биндит нужную реализацию порта по driver.

**DoD слоя 6:**
- drivers реально переключают интеграции
- тесты выбора реализаций

**PR title:** `layer6: integration drivers via config`

---

## Слой 7 — Avito как доменный Target, не External API

**Цель:** архитектура отражает реальность публикации.

Сделать одно из:
- удалить `AvitoAdapter.php` как порт
- перенести в доменный target/formatter:
  - `cabinet/backend/src/Modules/Publish/Targets/AvitoTargetFormatter.php`

**DoD слоя 7:**
- Avito не представлен как HTTP‑адаптер внешки
- Publish использует Robot/Dolphin порты

**PR title:** `layer7: avito as domain target`

---

## Слой 8 — Spec‑Max расширение модулей (серия PR)

**Цель:** реализовать максимум возможностей Spec в каждом модуле.

**Порядок PR (строго):**
1) Parser → `parser: spec-max`
2) Photos → `photos: spec-max`
3) Export → `export: spec-max`
4) Publish → `publish: spec-max`
5) Robot → `robot: spec-max`
6) Admin → `admin: spec-max`
7) Cards/Auth/Users → `core: spec-max`

### DoD для каждого модуля

В PR модуля должно быть:
- все статусы Spec
- все переходы Spec (state machine в Modules, не в Adapters)
- очередь/воркер полностью реализуют async ветки
- retry/DLQ статусы и ветки
- WS события на каждый переход + payload по Spec
- схемы API/WS + валидация
- unit+integration тесты на:
  - happy path
  - временную ошибку (retry)
  - фатальную ошибку (DLQ)
  - идемпотентность/повтор

### Нюансы по модулям (важно)

**Parser**
- поддержать push и poll режимы (если Spec требует)
- attachRawPhotos после инжеста
- после parser → enqueue photos

**Photos**
- raw → processor → store → attach
- setPrimary/reorder/delete
- прогресс WS

**Export**
- мульти‑стадия, download links/tokens
- метрики длительности стадий
- cancel/retry

**Publish + Robot**
- publish pipeline без прямых знаний о Dolphin/Robot реализациях
- RobotStatusWorker синхронизирует статусы
- WS по стадиям: queued/running/retrying/dead/done

**Admin**
- мониторинг очередей + DLQ replay
- карточка‑таймлайн пайплайна по card_id
- health dashboard по адаптерам
- force actions с RBAC
- audit log действий

---

## Слой 9 — Сквозной аудит связности (E2E + boundary)

**Цель:** подтвердить правильность взаимосвязей слоёв.

Сделать:
- fake full‑cycle e2e (`tests/e2e/full_cycle.e2e.test.ts`)
- boundary‑lint по правилам `AGENTS.md`
- consistency‑tests Spec ↔ API ↔ WS (если возможно)

**DoD слоя 9:**
- e2e зелёные
- boundary‑checks зелёные
- WS матрица совпадает со Spec

**PR title:** `layer9: e2e and boundary audit`

---

## Глобальные критерии завершения проекта

Проект считается доведённым до Spec‑Max, когда:
- все слои 0‑9 merged
- нет TODO в pipeline‑модулях
- любая смена интеграции делается только через Adapters + config
- контрактные несовпадения ловятся fail‑fast
- fake‑режим гоняет full‑flow в CI
- админка покрывает эксплуатацию конвейера
