# AGENTS.md — Autocontent Architecture + Codex Rules (v1, single source)

Этот файл совмещает:
1) **Архитектурный AGENT.md** (замороженный каркас, границы слоёв).
2) **Инструкции для Codex/AGENTs** — как работать в этом репо.

**Считать этот файл единственным источником истины для структуры.**

---

## 0) Контекст

Autocontent — конвейер авто-объявлений:

1) Парсер Auto.ru / auto-parser.ru **API** → данные + фото.  
2) Backend (**PHP**) создаёт карточки и управляет пайплайнами.  
3) PhotoPipeline: raw → **Photo API** (on-prem) → **S3 storage** (on-prem MinIO/FS).  
4) ExportPipeline: формирует пакеты → storage.  
5) PublishPipeline: **внутренний Robot** публикует через **Dolphin Anty** в Avito.  
6) Всё тяжёлое — через **очереди + воркеры + retry + DLQ**, статусы → WS.

Принцип: ядро решает **“что/когда”**, внешка выполняет **“как”**.  
Даже ваши on-prem сервисы остаются *external* по отношению к ядру.

### Принцип реализации качества
Любой модуль/воркер/адаптер реализуется **полностью по Autocontent Spec**:
- все статусы пайплайна
- все retry/DLQ ветки
- WS события
- схемы/валидации
- тесты unit + integration + e2e  
Частичная реализация считается незавершённой.

---

## 1) Замороженная структура (Tree)

> В текущем GitHub-репозитории корнем является `cabinet/`.  
> Структура ниже — эталонный каркас, а реальные пути в репо соответствуют ему с префиксом `cabinet/`.

**Корневые папки и расположение — заморожены.**  
Внутри модулей/фич можно добавлять файлы, **не ломая каркас**.

```text
cabinet/
├── README.md
├── AGENTS.md  (этот файл)
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
│   │   └── frontend/
│   ├── integration/
│   ├── e2e/
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
2) Backend (PHP) — границы и правила
Слои
Modules/ — домены и state machine.

Adapters/ — все интеграции: Parser/Photo/S3/Robot/Dolphin/Avito.

Queues/ — jobs + retry + DLQ.

Workers/ — асинхронное исполнение.

WS/ — realtime.

DB/ — хранение и миграции.

Обязательные правила зависимостей
Modules не вызывают внешку напрямую — только через Adapters.

Modules ставят jobs, Workers исполняют.

Workers импортируют Modules+Adapters, но не Controllers/Routes.

Adapters не импортируют Modules.

Robot внутри backend, но за RobotAdapter (Publish знает только адаптер).

3) External — правила
contracts/ — истина интерфейса.

fixtures/ — тестовые данные.

Если появится свой парсер → добавляем external/parser/service/, корень не меняем.

4) Frontend — правила слоёв
design/** → только UI/токены, без API.

shared/** → инфраструктура, не тянет features/apps.

features/** → бизнес-UI, импортирует design+shared.

apps/** → operator/admin композиция из features.

5) Авто-контроль структуры (минимум обязателен)
В CI должны быть:

lint (front+back)

boundary-lint (front+back)

unit tests

integration/e2e tests

PR, нарушающий границы, должен падать.

6) ADR — как менять скелет
Если всё-таки надо поменять скелет:

docs/architecture/ADR-XXXX-title.md

Шаблон:

markdown
Копировать код
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
Менять корень можно только после Accepted.

7) Запрещено
Backend
внешние HTTP/SDK вызовы из Modules

бизнес-логика в Adapters

воркеры внутри доменов

импорт Modules из Adapters

Frontend
бизнес/данные в design

features импортируют apps

shared тянет features

External
менять формат без contracts

========= Codex / Agent Instructions =========
Codex, follow everything above. This section adds execution rules.

8) Source of truth for behavior
Functional requirements live in Autocontent Spec.htm.

This file (AGENTS.md) defines repo structure and boundaries.

If Spec conflicts with boundaries, follow boundaries and propose compliant alternative.

9) How to work in this repo (Codex)
Always search for existing patterns before adding new ones.

Prefer extending existing Models/Services/Schemas/Workers.

Keep changes minimal per task, but implement the FULL required behavior for touched area.

Do not create new top-level folders.

Always propagate correlation_id through pipelines, logs, queues, WS.

10) Tests (REQUIRED)
For any behavior change, add/extend:

cabinet/tests/unit/backend

cabinet/tests/integration

cabinet/tests/e2e

Run:

npm run test:all

phpunit tests/unit/backend

phpunit tests/integration

npm run test:e2e

If tests fail — fix until green.

11) Code style
PHP 8.2+, strict types, typed props, explicit returns.

Prefer small pure methods.

Never swallow exceptions silently.

No business logic inside Adapters: only transport/translation.

12) Branch/PR behavior
Make changes on current branch.

Commit message: codex: <short summary>.

PR description must include:

what changed

why

how tested (exact commands)

13) When task is large
Before coding:

Write a short design in cabinet/PLANS.md

List affected files & steps

Wait for user approval in prompt thread
Then implement.

14) Review guidelines (for @codex review)
Flag P0/P1:

boundary violations

missing retries/DLQ for any async pipeline

missing WS status events for long tasks

security/auth regressions

schema mismatch between backend/frontend

makefile
Копировать код
::contentReference[oaicite:0]{index=0}
