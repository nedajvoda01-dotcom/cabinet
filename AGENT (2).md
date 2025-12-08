# AGENT.md — Autocontent Architecture & Repo Rules (v4, frozen tree)

Этот документ — **замороженная, но гибкая** схема репозитория Autocontent.  
Он задаёт **верхний скелет**, границы слоёв и правила зависимостей.  
Менять скелет можно только через ADR (Architecture Decision Record).

---

## 0) Контекст

Autocontent — конвейер авто‑объявлений:

1) Парсер Auto.ru / auto-parser.ru **API** → данные + фото.  
2) Backend (**PHP**) создаёт карточки и управляет пайплайнами.  
3) PhotoPipeline: raw → **Photo API** (on‑prem) → **S3 storage** (on‑prem MinIO/FS).  
4) ExportPipeline: формирует пакеты → storage.  
5) PublishPipeline: **внутренний Robot** публикует через **Dolphin Anty** в Avito.  
6) Всё тяжёлое — через **очереди + воркеры + retry + DLQ**, статусы → WS.

Принцип: ядро решает **“что/когда”**, внешка выполняет **“как”**.  
Даже ваши on‑prem сервисы остаются *external* по отношению к ядру.

---

## 1) Полная замороженная структура (Tree)

**Корневые папки и расположение — заморожены.**  
Внутри модулей/фич можно добавлять файлы, **не ломая каркас**.

```text
full_project/
├── README.md
├── AGENT.md
├── package.json
├── autostart.js
├── run_app.bat
├── docker-compose.yml
├── docker-compose.prod.yml
├── .env.example
├── .gitignore
├── .editorconfig
├── .eslintrc.cjs
├── .prettierrc
├── .github/
│   └── workflows/
│       ├── ci.yml
│       └── release.yml
├── docs/
│   ├── README.md
│   ├── architecture/
│   │   ├── C1_context.md
│   │   ├── C2_containers.md
│   │   ├── C3_components.md
│   │   └── state_machine.md
│   ├── api-docs/
│   │   ├── openapi.yaml
│   │   └── ws-events.md
│   └── runbooks/
│       ├── oncall.md
│       ├── dlq_handling.md
│       └── integrations.md
├── infra/
│   ├── nginx/
│   │   ├── nginx.dev.conf
│   │   └── nginx.prod.conf
│   ├── docker/
│   │   ├── backend.Dockerfile
│   │   ├── workers.Dockerfile
│   │   ├── frontend.Dockerfile
│   │   └── external.Dockerfile
│   └── k8s/
│       ├── backend.yaml
│       ├── workers.yaml
│       ├── frontend.yaml
│       ├── external.yaml
│       └── ingress.yaml
├── tests/
│   ├── unit/
│   │   ├── backend/
│   │   │   ├── cards.test.php
│   │   │   ├── photos.test.php
│   │   │   ├── export.test.php
│   │   │   ├── publish.test.php
│   │   │   ├── stateMachine.test.php
│   │   │   └── queue.test.php
│   │   └── frontend/
│   │       ├── reducers.test.ts
│   │       └── hooks.test.ts
│   ├── integration/
│   │   ├── parser.integration.test.php
│   │   ├── photos.integration.test.php
│   │   ├── export.integration.test.php
│   │   └── publish.integration.test.php
│   ├── e2e/
│   │   ├── full_cycle.e2e.test.ts
│   │   ├── dlq_retry.e2e.test.ts
│   │   └── admin_panel.e2e.test.ts
│   ├── fixtures/
│   └── mocks/
│
├── backend/
│   ├── README.md
│   ├── composer.json
│   ├── public/
│   │   └── index.php
│   └── src/
│       ├── Server/
│       ├── Config/
│       ├── Logger/
│       ├── DB/
│       ├── Middlewares/
│       ├── Routes/
│       ├── WS/
│       ├── Adapters/
│       ├── Queues/
│       ├── Workers/
│       ├── Utils/
│       └── Modules/
│
├── external/
│   ├── README.md
│   ├── docker-compose.yml
│   ├── parser/
│   │   ├── contracts/
│   │   └── fixtures/
│   ├── photo-api/
│   │   ├── contracts/
│   │   ├── fixtures/
│   │   └── service/
│   ├── storage/
│   │   ├── contracts/
│   │   ├── fixtures/
│   │   └── service/
│   ├── dolphin/
│   │   └── contracts/
│   └── avito/
│       └── contracts/
│
└── frontend/
    ├── README.md
    ├── package.json
    └── src/
        ├── design/
        ├── shared/
        ├── features/
        ├── apps/
        ├── AppShell.tsx
        └── index.tsx
```

---

## 2) Backend (PHP) — границы и правила

### Слои
- **Modules/** — домены и state machine.  
- **Adapters/** — все интеграции: Parser/Photo/S3/Robot/Dolphin/Avito.  
- **Queues/** — jobs + retry + DLQ.  
- **Workers/** — асинхронное исполнение.  
- **WS/** — realtime.  
- **DB/** — хранение и миграции.

### Обязательные правила
1) **Modules не вызывают внешку напрямую** — только через Adapters.  
2) **Modules ставят jobs**, Workers исполняют.  
3) **Workers импортируют Modules+Adapters**, но не Controllers/Routes.  
4) **Adapters не импортируют Modules.**  
5) **Robot внутри backend, но за RobotAdapter** (Publish знает только адаптер).

---

## 3) External — правила

1) **contracts/** — истина интерфейса.  
2) **fixtures/** — тестовые данные.  
3) Если появится свой парсер → добавляем `external/parser/service/`, корень не меняем.

---

## 4) Frontend — правила слоёв

1) `design/**` → только UI/токены, без API.  
2) `shared/**` → инфраструктура, не тянет features/apps.  
3) `features/**` → бизнес‑UI, импортирует design+shared.  
4) `apps/**` → operator/admin композиция из features.

---

## 5) Авто‑контроль структуры (минимум обязателен)

В CI должны быть:
- lint (front+back)
- boundary‑lint (front+back)
- unit tests
- integration/e2e tests

PR, нарушающий границы, должен падать.

---

## 6) ADR — как менять скелет

Если всё‑таки надо поменять скелет:

`docs/architecture/ADR-XXXX-title.md`

Шаблон:
```markdown
# ADR-XXXX: title
## Status
Proposed | Accepted | Rejected | Deprecated
## Context
Почему меняем.
## Decision
Что меняем.
## Consequences
Плюсы/минусы/риски/миграция.
## Alternatives considered
Что ещё было и почему отказались.
```

Менять корень можно только после `Accepted`.

---

## 7) Запрещено

### Backend
- внешние HTTP/SDK вызовы из Modules  
- бизнес‑логика в Adapters  
- воркеры внутри доменов  
- импорт Modules из Adapters

### Frontend
- бизнес/данные в design  
- features импортируют apps  
- shared тянет features

### External
- менять формат без contracts
