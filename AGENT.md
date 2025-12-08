# AGENT.md — Autocontent Architecture & Repo Rules (v3)

Этот документ — **замороженная, но гибкая** схема репозитория Autocontent.  
Он задаёт **верхний скелет**, границы слоёв и правила зависимостей.  
Менять скелет можно только через ADR (Architecture Decision Record).  
Менять внутренности модулей/фич — можно свободно, если границы не нарушаются.

---

## 0) Что такое Autocontent (контекст)

Autocontent — конвейер автоматизации объявлений авто:

1. **Парсер Auto.ru / auto-parser.ru API** поставляет готовые данные и ссылки на фото.
2. **Backend (PHP)** принимает JSON, нормализует и создаёт **карточки**.
3. Запускается **PhotoPipeline**: сырьё → маскирование номеров (ваш Photo API) → загрузка в on‑prem S3‑storage (MinIO/FS gateway).
4. Операторы в UI доводят карточки до готовности.
5. **ExportPipeline** формирует Excel/JSON пакеты и складывает их в storage.
6. **PublishPipeline** публикует через **внутренний Robot**, который работает в Dolphin Anty и кликает Avito.
7. Статусы пайплайнов пушатся в UI через WebSocket.
8. Долгие/хрупкие операции выполняются асинхронно: **очереди + воркеры + retry + DLQ**.

Главный принцип: **ядро управляет “что и когда”**, а интеграции/сервисы выполняют “как именно”.  
Даже если сервис ваш и на вашем сервере — он остаётся внешним по отношению к ядру.

---

## 1) Замороженный скелет репозитория (Root)

Ниже корневое дерево. **Эти папки нельзя удалять/переносить/переименовывать**.

```
full_project/
├── README.md
├── AGENT.md
├── package.json
├── docker-compose.yml
├── docker-compose.prod.yml
├── .env.example
├── .github/workflows/
├── docs/
├── infra/
├── tests/
├── backend/      # PHP ядро
├── frontend/     # React UI (слоистый)
└── external/     # реальные on‑prem сервисы + контракты внешних API
```

---

## 2) Backend (PHP) — ядро + internal‑robot‑behind‑adapter

### 2.1 Структура backend

```
backend/
├── composer.json
├── public/
│   └── index.php
└── src/
    ├── Server/
    ├── Config/
    ├── Logger/
    ├── DB/
    ├── Middlewares/
    ├── Routes/
    ├── WS/
    ├── Adapters/
    ├── Queues/
    ├── Workers/
    ├── Utils/
    └── Modules/
```

### 2.2 Назначение слоёв backend

- **Modules/** — домены (бизнес‑логика, правила, state machine).
- **Adapters/** — 100% интеграций (HTTP/CLI/SDK) с внешними сервисами.
- **Queues/** — инфраструктура задач, retry, DLQ.
- **Workers/** — фоновые consumers (Photo/Export/Publish/Parser/RobotStatus).
- **WS/** — realtime‑шлюз (push статусов в UI).
- **DB/** — миграции/репозитории.
- **Middlewares/** — auth/roles/validation/errors.
- **Utils/** — общие доменные helper’ы.

### 2.3 Правила зависимостей backend (обязательные)

1) **Modules НЕ ходят во внешку напрямую.**  
   Домены общаются с внешним миром ТОЛЬКО через Adapters.

✅ разрешено:
```php
$photoApi = $this->adapters->photoApi();
$photoApi->process($images);
```

❌ запрещено:
```php
$client = new GuzzleHttp\Client(); // внутри Modules — нельзя
```

2) **Modules ставят задачи в Queues, Workers исполняют.**  
   Домен не делает долгую работу синхронно.

3) **Workers могут импортировать Modules + Adapters, но не Routes/Controllers.**

4) **Adapters НЕ импортируют Modules.**  
   Чтобы не было циклов: адаптер — чистый I/O слой.

5) **Robot реализован “внутри” backend, но за RobotAdapter.**  
   - `Modules/Publish` вызывает только `RobotAdapter::publish()`  
   - `RobotAdapter` внутри МОЖЕТ дергать `Modules/Robot` напрямую  
   - при выносе робота во внешний сервис меняется только адаптер/конфиг.

---

## 3) External — реальные on‑prem сервисы + контракты внешних API

### 3.1 Структура external (без эмуляторов)

```
external/
├── parser/        # внешний Auto‑parser API (пока без service)
│   ├── contracts/
│   └── fixtures/
├── photo-api/     # ваш on‑prem сервис маскировки
│   ├── contracts/
│   ├── fixtures/
│   └── service/
├── storage/       # ваш on‑prem S3 (MinIO/FS gateway)
│   ├── contracts/
│   ├── fixtures/
│   └── service/
├── dolphin/       # Dolphin Anty API
│   └── contracts/
└── avito/         # Avito (работа через робот)
    └── contracts/
```

### 3.2 Правила external

1) **Contracts — истина интерфейса**, от них зависят adapters и тесты.  
2) **Fixtures — для интеграционных/е2е тестов и QA.**  
3) **Если позже пишется свой парсер — добавляем `external/parser/service/` (Node/Puppeteer),  
   скелет не меняем.**

---

## 4) Frontend — слоистый React + 2 apps (operator/admin)

### 4.1 Структура frontend

```
frontend/src/
├── design/     # конечный дизайн / UI kit
├── shared/     # api/ws/store/hooks/utils/guards
├── features/   # доменные фичи
├── apps/       # operator + admin
├── AppShell.tsx
└── index.tsx
```

### 4.2 Правила зависимостей фронта

1) **design** не импортирует ничего кроме design.  
2) **shared** не импортирует features/apps.  
3) **features** импортируют только design + shared.  
4) **apps** импортируют только features + shared/guards.

---

## 5) Системные взаимодействия (кто с кем говорит)

### 5.1 Основной flow

```
Parser API ──> ParserAdapter ──> Modules/Parser ──> Cards.createDraft
                                      │
                                      ├─ enqueue photo.process ─> PhotoWorker
                                      │                              ├─ PhotoApiAdapter -> Photo API (on‑prem)
                                      │                              ├─ S3Adapter -> Storage (on‑prem)
                                      │                              └─ WS photo.update
                                      │
                                      ├─ enqueue export.generate ─> ExportWorker
                                      │                              ├─ ExportGenerator
                                      │                              ├─ S3Adapter -> Storage
                                      │                              └─ WS export.ready
                                      │
                                      └─ enqueue publish.run ─────> PublishWorker
                                                                     ├─ RobotAdapter -> Internal Robot
                                                                     │                    └─ Dolphin API -> Avito
                                                                     └─ WS publish.update
```

### 5.2 DLQ

```
Worker fail -> RetryPolicy
attempts > maxAttempts -> DlqStorage
Admin UI -> /admin/dlq -> retry/cancel
```

---

## 6) Нейминг и размещение файлов

### Backend (внутри Modules/<Domain>/)

Обязательный каркас домена:

```
Modules/<Domain>/
├── <Domain>Controller.php
├── <Domain>Service.php
├── <Domain>Model.php (или Repository.php)
├── <Domain>Schemas.php
└── <Domain>Jobs.php        # если домен ставит async‑работы
```

### Frontend (внутри features/<domain>/)

```
features/<domain>/
├── api.ts
├── model.ts
├── schemas.ts
├── ui/
└── index.ts
```

---

## 7) Автоматическое соблюдение структуры (обязательный минимум)

Чтобы схема не расползалась, в проекте должны быть включены проверки:

### 7.1 Lint‑границы импортов

**Frontend (eslint-plugin-boundaries или import/no-restricted-paths):**
- `design/**` не импортирует ничего вне `design/**`
- `shared/**` не импортирует `features/**` и `apps/**`
- `features/**` не импортирует `apps/**`
- `apps/**` не импортирует внутренности чужих apps

**Backend (linters/статический анализ):**
- `Modules/**` не могут зависеть от HTTP‑клиентов/SDK напрямую
- `Adapters/**` не могут импортировать `Modules/**`
- `Workers/**` не импортируют `Routes/**`

### 7.2 CI

В `.github/workflows/ci.yml` должны быть шаги:
- lint (front+back)
- boundary‑lint (front+back)
- unit tests
- integration tests (на dev‑стенде)

PR, нарушающий границы, должен падать.

### 7.3 Генераторы скелетов (рекомендуется)

Желательно иметь команды:
- `gen:module <name>` → создаёт каркас `Modules/<Name>/`
- `gen:feature <name>` → создаёт каркас `features/<name>/`

Это быстрее и экономит нервы.

---

## 8) Что категорически запрещено

### Backend
- HTTP/SDK вызовы внешних сервисов из Modules.
- Бизнес‑логика внутри Adapters.
- Воркеры внутри доменов.
- Импорт Modules из Adapters.

### Frontend
- API/бизнес‑логика в design.
- Импорт apps из features.
- shared, который тянет features наверх.

### External
- Изменение формата без обновления contracts.
- Добавление произвольных полей без фиксации контракта.

---

## 9) Как добавлять новое (универсальный сценарий)

### Новый домен backend
1. Создай `backend/src/Modules/<Domain>/`.
2. Добавь каркас файлов домена (controller/service/model/schemas/jobs).
3. Подключи routes в `backend/src/Routes/routes.php`.
4. Если домен требует внешки — добавь/расшири адаптер в `Adapters/`.

### Новая фича frontend
1. Создай `frontend/src/features/<domain>/` (api/model/schemas/ui/index).
2. В apps подключи страницу/виджет, не лезя в бизнес‑логику.

### Новый внешний сервис (наш/on‑prem или third‑party)
1. Создай `external/<service>/{contracts,fixtures,service}`.
2. Опиши контракт.
3. Реализуй service.
4. Подключи адаптер в backend.

---

## 10) ADR — как менять скелет, если всё‑таки нужно

Если требуется изменить замороженный скелет:

1. Создаём файл:
```
docs/architecture/ADR-XXXX-<short-title>.md
```

2. Шаблон ADR:

```markdown
# ADR-XXXX: <title>

## Status
Proposed | Accepted | Rejected | Deprecated

## Context
Почему нужна смена (бизнес/тех причины).

## Decision
Что именно меняем в скелете.

## Consequences
Плюсы/минусы, риски, как мигрировать код.

## Alternatives considered
Какие варианты рассматривались и почему отказались.
```

3. Только после Accepted ADR разрешается менять корневую схему.

---

## 11) Зачем мы замораживаем этот скелет

Скелет гарантирует:

- заменяемость интеграций (провайдеры/наши сервисы меняются без переписывания ядра);
- устойчивость конвейера (очереди, retry, DLQ);
- чистые доменные границы (cards как центр);
- слоистый фронт без спагетти;
- масштабируемость команды и быстрый онбординг.

Менять можно всё внутри доменов/фич, **но не нарушая границы слоёв**.
