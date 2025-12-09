Autocontent Spec
v1 • структура и смысл системы

Вверх

## Содержание

Parts

[Part 1. Введение / функционал программы](#p1) [Part 2. Пользователи, роли и уровни фронтенда](#p2) [Part 3. End-to-End сценарий (полный цикл)](#p3) [Part 4. Архитектура системы и модули](#p4) [Part 5. Данные, сущности и хранилища](#p5) [Part 6. Очереди, воркеры и надежность pipeline](#p6) [Part 7. Интеграции и контракты внешних сервисов](#p7) [Part 8. API ядра и WebSocket события](#p8) [Part 9. Контракты, схемы и версии данных](#p9) [Part 10. State Machine карточки и переходы pipeline](#p10) [Part 11. Reliability, SLA и эксплуатационные правила](#p11) [Part 12. RBAC, права доступа и feature flags](#p12) [Part 13. Схема БД, индексация и миграции](#p13) [Part 14. Архитектура фронтенда (слои, apps, features)](#p14) [Part 15. Правила фасада UI и дизайн-система продукта](#p15) [Part 16. Operator UI — ключевые экраны и логика](#p16) [Part 17. Admin UI — ключевые экраны и логика](#p17) [Part 18. Инфраструктура, деплой и окружения](#p18) [Part 19. Monitoring, бэкапы и Disaster Recovery](#p19) [Part 20. Тестирование, CI/CD и контроль качества](#p20) [Part 21. MVP-объем, roadmap и порядок реализации](#p21) [Part 22. Глоссарий, термины и правила нейминга](#p22) [Part 23. Правила работы с репозиторием (CONTRIBUTING)](#p23) [Part 24. API фасад, эндпоинты и правила совместимости](#p24) [Part 25. WS события и real-time протокол](#p25) [Part 26. Contracts-first подход и версионирование](#p26) [Part 27. Безопасность, приватность и compliance](#p27) [Part 28. Производительность и масштабирование](#p28) [Part 29. Риски, допущения и открытые решения](#p29) [Part 30. Финальная фиксация архитектуры и резюме](#p30)

Appendix

[Part 31. Appendix A — Сквозной сценарий (happy path) в псевдокоде](#p31) [Part 32. Appendix B — Чек-листы запуска и приемки](#p32) [Part 33. Appendix C — Шаблоны экранов и UI-паттерны](#p33) [Part 34. Appendix D — Модель данных (ERD текстом)](#p34) [Part 35. Appendix E — Таблица State Machine](#p35) [Part 36. Appendix F — Примеры конфигов и feature flags](#p36) [Part 37. Appendix G — Каталог ошибок и retry-политика](#p37) [Part 38. Appendix H — Инструкции для Codex/агента](#p38) [Part 39. Appendix I — Quick Start для новых разработчиков](#p39) [Part 40. Appendix J — Топология деплоя и окружения](#p40) [Part 41. Appendix K — Метрики, SLA и дашборды](#p41) [Part 42. Appendix L — Релизы, миграции и rollback](#p42) [Part 43. Appendix M — Phase 2: план развития и точки расширения](#p43) [Part 44. Appendix N — Глоссарий терминов Autocontent](#p44) [Part 45. Appendix O — Регламенты ролей и ответственности](#p45)


Part 1.
 Введение / функционал программы

 Что такое Autocontent, кому он нужен и как выглядит итоговый результат конвейера.


L1 — общее понимание

### Определение

 Autocontent — это управляемая система-конвейер, которая автоматически получает объявления
 из Auto.ru (через auto-parser.ru), нормализует их в карточки, обрабатывает фотографии
 (маскировка номеров), формирует экспортные пакеты и публикует объявления на Avito
 через внутреннего робота (Dolphin Anty → Avito).


### Цель для отдела

* Сократить ручной труд в подготовке объявлений.
* Ускорить выпуск на площадку без потери качества.
* Уменьшить число ошибок за счёт статусов и проверок.
* Сделать поток наблюдаемым и управляемым.

### Что автоматизируем

* Получение и нормализацию данных объявлений.
* Фото-пайплайн: маскирование и сортировка фото.
* Сбор экспортных пакетов (Excel/JSON).
* Публикацию и контроль статусов робота.
* Retry и DLQ для ошибок интеграций.

### Что считаем результатом

* Опубликованные объявления на Avito.
* Экспортные пакеты для выгрузки.
* История статусов/ошибок по карточкам.
* Админ-контроль очередей и DLQ.

### Конвейер

Parser
Вход

 Получаем JSON объявлений и raw-фото с Auto.ru через auto-parser.ru API.


Cards
Ядро / нормализация

 Создаём карточку, приводим данные к единому формату, назначаем статусы pipeline.


Photos
Очередь / обработка

 Отправляем фото в Photo API, получаем masked-версии, фиксируем порядок.


Export
Очередь / пакеты

 Собираем экспортные файлы из готовых карточек, сохраняем историю экспортов.


Publish
Выход / робот

 Robot публикует на Avito через Dolphin Anty и возвращает статус размещения.


WS статусы

 Все изменения в pipeline транслируются в UI в реальном времени
 (прогресс фото, экспортов и публикации).


DLQ ошибки

 Фатальные сбои не теряются: задача попадает в DLQ, видна администратору,
 доступен ручной retry.


### Границы продукта

### Что входит в v1 (MVP)

* Auto.ru через auto-parser.ru API.
* Карточки и жизненный цикл (pipeline-статусы).
* Фото-пайплайн: маскирование номеров → masked-фото.
* Экспорт пакетов.
* Публикация через Robot (Dolphin → Avito).
* Очереди, retry, DLQ, health-контроль.

### Что не входит в v1

* Многоисточниковый парсинг.
* AI-генерация / переписывание описаний.
* CRM / продажи / лид-менеджмент.
* Маркетинговая аналитика площадок.

### Артефакты на стадиях

| Шаг | Артефакт | Где лежит | Зачем фиксируем |
| --- | --- | --- | --- |
| **Parser** | CardDraft + raw-фото | DB.cards + Storage/raw | Стартовый пакет данных, исходник для карточки. |
| **Cards** | Card (единая сущность) | DB.cards (+audit) | Источник правды по объявлению и статусы pipeline. |
| **Photos** | Masked-фото + порядок | DB.photos + Storage/masked | Готовый медиаконтент без номеров. |
| **Export** | Export-пакет | DB.exports + Storage/exports | Выгрузка для публикации и отчётности. |
| **Publish** | PublishJob + статусы | DB.publish\_jobs + Avito | Фиксация результата размещения и ошибок робота. |

 Детальные статусы и переходы фиксируются в Part 10 (State Machine).


### Критерии успеха MVP

1. Карточка создаётся автоматически из входа парсера (draft).
2. Фото маскируются и появляются как masked-набор.
3. Оператор проверяет/исправляет и ставит “готово к экспорту”.
4. Экспортный пакет создаётся и доступен для выгрузки.
5. Публикация запускается роботом и возвращает статус.
6. Ошибки видны и не теряются: статус + DLQ.

Что появится позже
* Многоисточниковый парсинг.
* AI-редактор и генерация описаний.
* Аналитика эффективности Avito.

**Утверждено:**
 Autocontent — pipeline-система, автоматизирующая путь
 от Auto.ru до публикаций Avito с прозрачными стадиями и контролем ошибок.


**Открытые вопросы:**
 формат входа auto-parser.ru и полный набор полей карточки фиксируем в Part 9.



Part 2.
 Пользователи, роли и уровни фронтенда

 Кто работает в системе, какие у них цели, права и как это отражается в двух уровнях UI.


L1 — общее понимание

### Смысл ролевой модели

 Роли определяют доступ к стадиям pipeline и к административным функциям.
 На фронтенде это выражено отдельными приложениями: **Operator UI** (рабочее)
 и **Admin UI** (управление системой). Один пользователь может иметь несколько ролей,
 но интерфейс и доступы формируются по активной роли.


### Operator (Оператор)

* Работает с карточками и их качеством.
* Запускает/контролирует стадии Photos, Export, Publish.
* Исправляет данные, фото, описания внутри карточки.
* Видит статусы pipeline по своим карточкам.

 Цель: довести карточку до публикации без технических действий “вручную”.


### Admin (Администратор)

* Управляет очередями, ретраями и DLQ.
* Смотрит логи, метрики, health интеграций.
* Управляет пользователями и ролями.
* Может вручную перекидывать задачи между статусами.

 Цель: стабильность потока и скорость восстановления при сбоях.


### Superadmin / Owner (Руководитель)

* Имеет полный доступ ко всем разделам.
* Утверждает настройки интеграций и фичефлаги.
* Смотрит отчёты по эффективности pipeline.

 Цель: контроль продукта и его бизнес-эффекта.


### Два уровня фронтенда

### Operator UI (рабочий контур)

* Ежедневная работа с карточками.
* Таблица карточек + фильтры + групповые действия.
* Просмотр/правка данных и фото.
* Запуск export/publish, контроль прогресса.
* Получение live-статусов через WS.

 Точка входа: `frontend/src/apps/operator`.


### Admin UI (системный контур)

* Очереди: текущее состояние, скорость, ретраи.
* DLQ: список упавших задач, причины, ручной retry.
* Логи и аудит действий.
* Интеграции: статусы Parser / Photo API / Storage / Dolphin.
* Пользователи и роли.

 Точка входа: `frontend/src/apps/admin`.


### Матрица доступов (L1)

| Секция / действие | Operator | Admin | Superadmin/Owner |
| --- | --- | --- | --- |
| **Cards: просмотр и фильтры** | Да | Да | Да |
| **Cards: правка данных/фото** | Да | Ограниченно* | Да |
| **Photos / Export / Publish запуск** | Да | Да | Да |
| **Queues / Retry / DLQ** | Нет | Да | Да |
| **Integrations health/config** | Нет | Да (health) | Да (health+config) |
| **Users / Roles управление** | Нет | Да | Да |

 * Admin может корректировать карточку только при разборе аварийных случаев (например, ручное снятие блокировки).
 Подробная матрица прав будет расширена в Part 12 (RBAC).


### Переключение и наследование ролей

* Роль назначается пользователю в Admin UI.
* При входе пользователь попадает в интерфейс своей основной роли.
* Если ролей несколько — доступно переключение “режима” (Operator ↔ Admin).
* Сессия и токены общие, но права проверяются на уровне бэкенда и фронта.

**Утверждено:**
 В системе есть минимум три роли (Operator, Admin, Superadmin), а фронтенд разделён на два контура:
 рабочий (Operator UI) и системный (Admin UI).


**Открытые вопросы:**
 детальная RBAC-матрица и edge-кейсы (например, оператор с ограниченным publish) — Part 12.



Part 3.
 End-to-End сценарий (полный цикл)

 Как одна карточка проходит весь путь: от входа из Auto.ru до публикации на Avito и контроля статусов.


L1 — общий жизненный цикл

### Суть E2E

 End-to-End сценарий описывает “идеальный” поток без ручных обходов:
 система сама создаёт карточку, подготавливает фото, формирует экспорт, публикует,
 а оператор и админ видят прогресс и вмешиваются только при необходимости.


### Этапы цикла

### 0. Триггер парсинга

 Внешний Parser (auto-parser.ru) по расписанию/запросу достаёт объявления Auto.ru
 и пушит их в Autocontent.


 Артефакт: входной JSON + raw-фото.


### 1. Создание карточки (Cards.draft)

 Ядро принимает входные данные, нормализует поля и создаёт Card в статусе
 **draft**. Карточка появляется в Operator UI.


 Источник правды: DB.cards.


### 2. Запуск фото-пайплайна

 Система кладёт PhotoJob в очередь. Worker забирает задачу, отправляет raw-фото
 в Photo API, получает masked-версии и сохраняет их в Storage.


 Статусы фото транслируются в UI через WS.


### 3. Проверка оператором

 Оператор открывает карточку, проверяет корректность данных и фото,
 при необходимости правит, после чего переводит карточку в статус
 **ready\_for\_export**.


 В этот момент карточка считается “готовой к упаковке”.


### 4. Формирование экспортного пакета

 ExportWorker формирует пакет (Excel/JSON) из выбранных карточек,
 сохраняет его в Storage и фиксирует запись в DB.exports.


 Карточка получает статус: **exported**.


### 5. Публикация роботом

 PublishWorker создаёт PublishJob и вызывает Robot.
 Robot через Dolphin Anty открывает Avito и размещает объявление,
 возвращая статус выполнения.


 Итог: **published** или **publish\_failed**.


### 6. Контроль статусов и завершение

 Система продолжает опрашивать Robot/Avito по статусам публикации,
 обновляет карточку и показывает финальное состояние в UI.


 Все изменения пишутся в audit-log.


### Два варианта исхода

### Happy Path

1. Parser → draft.
2. Фото обработаны → photos\_ready.
3. Оператор подтвердил → ready\_for\_export.
4. Export создан → exported.
5. Robot опубликовал → published.

### Fail Path

1. Ошибка внешнего сервиса / таймаут / невалидные данные.
2. Задача ретраится по RetryPolicy.
3. Если лимит исчерпан → DLQ.
4. Admin видит DLQ, чинит причину, делает retry.

 Важное правило: карточка никогда не “пропадает”, она либо в статусе, либо в DLQ.


### Лента статусов карточки (L1)

 draft
 → photos\_processing
 → photos\_ready
 → ready\_for\_export
 → exporting
 → exported
 → publishing
 → published


 Точная state machine с переходами, ретраями и guard-условиями — Part 10.


### Ответственность системы и людей

### Система делает сама

* Принимает вход из Parser.
* Создаёт карточку и статусы.
* Гоняет фоновые очереди (Photos/Export/Publish).
* Хранит фото и пакеты.
* Логирует ошибки, ретраит, складывает в DLQ.

### Человек делает точечно

* Оператор проверяет качество карточки и фото.
* Оператор подтверждает готовность к экспорту/публикации.
* Admin вмешивается только при DLQ/сбоях интеграций.

**Утверждено:**
 Полный E2E-цикл карточки проходит через Parser → Cards → Photos → Export → Publish
 с live-статусами в UI и DLQ для фатальных ошибок.


**Открытые вопросы:**
 SLA по каждому этапу, лимиты ретраев и форматы ошибок формализуем в Part 11 (Reliability).



Part 4.
 Архитектура системы и модули

 Из каких частей состоит Autocontent, что является ядром, что — внешними сервисами,
 и как они связываются между собой.


L1 — контейнерный уровень

### Архитектурная идея

 Autocontent построен как **ядро (backend)** + набор **адаптеров**
 к внешним сервисам. Даже если сервисы физически на нашем сервере (Photo API, Storage),
 логически они считаются внешними: ядро общается с ними только по контрактам.
 Это даёт заменяемость интеграций без переписывания бизнес-логики.


### Контейнеры системы

### Backend (ядро)

* Единая бизнес-логика pipeline.
* Хранилище карточек и статусов (DB).
* Очереди, ретраи, DLQ.
* Адаптеры к интеграциям.
* API для Operator/Admin UI.

 Репо: `backend/`

### Workers (фоновые процессы)

* ParserWorker — приём/обновление карточек.
* PhotoWorker — обработка фото.
* ExportWorker — сбор пакетов.
* PublishWorker — публикация.
* StatusWorker — синхронизация статусов робота/Avito.

 Репо: `backend/src/Workers`

### Frontend (2 контура)

* Operator UI — рабочий контур.
* Admin UI — системный контур.
* Shared слой API/Store/Guard’ов.
* Design слой UI-kit.

 Репо: `frontend/`

### Внешние сервисы (логически External)

### Parser API

 auto-parser.ru даёт готовый парсинг Auto.ru и отдаёт объявления
 в виде JSON + raw фото.


 Адаптер: `backend/src/Adapters/ParserAdapter.php`

### Photo API (on-prem)

 Наш сервис маскировки номеров.
 Принимает raw фото → возвращает masked фото.


 Адаптер: `PhotoApiAdapter.php`

### Storage (on-prem S3)

 S3-совместимое хранилище (MinIO/on-prem).
 Границы: raw/ masked/ exports/.


 Адаптер: `S3Adapter.php`

### Dolphin Anty API

 Внешний антибраузер: профили/сессии, через которые Robot
 идёт на Avito.


 Адаптер: `DolphinAdapter.php`

### Robot Service (внутренний)

 Реализация робота внутри проекта, но спрятана за интерфейсом адаптера.
 Для ядра это “чёрный ящик публикации”.


 Адаптер: `RobotAdapter.php`

### Avito

 Целевая площадка. Robot публикует карточки и читает статусы.
 Ядро хранит маппинг статусов Avito → pipeline.


 Адаптер: `AvitoAdapter.php`

### Главные связи между модулями

 ParserAdapter → CardsModule(draft)
 → Queues(Photos/Export/Publish)
 → Workers
 → PhotoApiAdapter / S3Adapter / RobotAdapter / DolphinAdapter / AvitoAdapter
 → CardsModule(status update)
 → WS → Frontend apps


 На L1 важно: ядро не знает деталей внешних сервисов; оно знает только их интерфейсы и контракты.


### Модульная структура ядра (backend)

### Доменные модули

* **Cards** — центр системы, статусы pipeline.
* Parser — приём и нормализация входа.
* Photos — правила фото-пайплайна.
* Export — генерация пакетов.
* Publish — оркестрация публикаций.
* Robot — внутренняя логика робота.

### Системные модули

* Auth / Users — доступ и роли.
* Queues — очередь, retry, DLQ.
* WS — live-ивенты в UI.
* Admin — health, логи, DLQ-интерфейс.
* Utils — state machine, idempotency, locks.

**Утверждено:**
 Архитектура строится вокруг backend-ядра и адаптеров к логически внешним сервисам.
 Frontend разделён на Operator/Admin контуры, а pipeline исполняется через очереди и воркеры.


**Открытые вопросы:**
 детальные контракты интеграций и формат событий WS фиксируем в Part 7 и Part 8.



Part 5.
 Данные, сущности и хранилища

 Какие основные сущности есть в Autocontent, что они содержат и где физически хранятся.


L1 — модель данных сверху

### Идея модели данных

 Система строится вокруг одной центральной сущности — **Card**.
 Все остальные сущности (Photos, Export, PublishJob, AuditLog) либо
 “принадлежат карточке”, либо описывают её обработку на стадиях pipeline.


### Сущности системы

### Card

* Единый объект объявления.
* Нормализованные поля Auto.ru.
* Текущий статус pipeline.
* Ссылки на фото, экспорт, публикации.

 Владелец цикла: CardsModule.


### Photo

* Набор фото одной карточки.
* raw → masked → порядок.
* Статус обработки каждого фото.
* Ошибки/ретраи по фото.

 Владелец цикла: PhotosModule.


### Export

* Пакет выгрузки из N карточек.
* Формат: Excel/JSON.
* Версия/дата/автор.
* Ссылка на файл в Storage.

 Владелец цикла: ExportModule.


### PublishJob

* Задача публикации одной карточки.
* Текущий статус робота/Avito.
* Retry/ошибки/история.

 Владелец цикла: PublishModule.


### QueueJob (системная)

* Задача фоновой обработки.
* Тип: photo/export/publish/parser/status.
* RetryPolicy + DLQ-ссылка.

 Владелец: Queues subsystem.


### AuditLog (системная)

* Все изменения карточек и действий пользователей.
* Кто/когда/что изменил.
* Для расследований и отчетов.

 Владелец: Logger/AuditLogger.


### Хранилища

### DB (PostgreSQL/MySQL)

* Cards
* Photos metadata
* Exports metadata
* PublishJobs
* Queues / DLQ
* Users / Roles
* Audit logs

 DB — источник правды по статусам и истории.


### S3 Storage (MinIO/on-prem)

* **raw/** — исходные фото из Parser.
* **masked/** — фото после Photo API.
* **exports/** — экспортные пакеты.
* **tmp/** — временные файлы воркеров.

 Storage хранит тяжёлые бинарные данные, DB хранит ссылки.


### Связи сущностей (L1)

| Связь | Тип | Смысл |
| --- | --- | --- |
| **Card → Photos** | 1 : N | У одной карточки множество фото (raw/masked). |
| **Card → PublishJobs** | 1 : N | Одна карточка может публиковаться несколько раз (повторные попытки/переиздания). |
| **Export → Cards** | N : N | Пакет состоит из набора карточек; одна карточка может попасть в несколько экспортов. |
| **QueueJob → Card/Photo/Export/PublishJob** | N : 1 | Каждая задача очереди ссылается на целевой доменный объект. |
| **AuditLog → *** | N : 1 | Аудит относится к конкретной сущности и фиксирует её изменение. |

### Минимальный состав Card (L1)

 Card = {
 id,
 source: "auto\_ru",
 source\_id,
 status, // pipeline status
 vehicle: { make, model, year, body, mileage, vin? },
 price: { value, currency },
 location: { city, address?, coords? },
 description,
 photos: [ {id, raw\_url, masked\_url, order, status} ],
 export\_refs: [export\_id...],
 publish\_refs: [publish\_job\_id...],
 created\_at, updated\_at
 }


 Полный контракт Card, все поля Auto.ru и правила валидации — Part 9 (Contracts & Schemas).


**Утверждено:**
 Центральная сущность — Card. Остальные сущности обслуживают её pipeline,
 а тяжёлые файлы живут в S3 Storage, метаданные и статусы — в DB.


**Открытые вопросы:**
 точная схема БД и индексация под фильтры Operator UI — Part 13 (DB Schema).



Part 6.
 Очереди, воркеры и надежность pipeline

 Как фоновые стадии выполняются асинхронно, как устроены ретраи и почему карточки не “теряются”.


L1 — уровень исполнения

### Идея надежности

 Все тяжёлые и нестабильные операции (фото, экспорт, публикация, синхронизация статусов)
 выполняются через очередь и воркеры. Это отделяет UI и ядро от задержек и сбоев внешних сервисов,
 а также позволяет масштабировать нужную стадию отдельно.


### Почему всё через очередь

### Стабильность

* Внешние API могут падать или тормозить.
* Очередь сглаживает пики и даёт ретраи.
* UI не блокируется долгими задачами.

### Масштабирование

* Можно добавлять воркеры только для узкого места.
* Например, увеличить PhotoWorker без влияния на Publish.
* Поддержка параллельной обработки.

### Прозрачность

* Каждая задача видна по статусу.
* Есть история, ошибки и попытки.
* DLQ фиксирует фатальные сбои.

### Типы очередей

| Очередь | Что обрабатывает | Кто потребляет | Результат |
| --- | --- | --- | --- |
| **photos** | Маскирование и подготовка фото | PhotoWorker | masked-фото + статус photos\_ready |
| **export** | Формирование экспортных пакетов | ExportWorker | export-файл + статус exported |
| **publish** | Публикация карточек | PublishWorker | объявление на Avito + статус published/publish\_failed |
| **parser** | Приём входа от Parser API | ParserWorker | CardDraft → Card(draft) |
| **robot\_status** | Синхронизация статусов робота/Avito | RobotStatusWorker | обновление publish-статусов |

### Модель исполнения задач (L1)

 QueueJob = {
 id,
 type: ["photos" | "export" | "publish" | "parser" | "robot\_status"],
 entity\_ref: { entity: "card|photo|export|publish\_job", id },
 payload,
 attempts,
 next\_retry\_at,
 status: ["queued" | "processing" | "retrying" | "done" | "dead"],
 last\_error?
 }


 Реализация очереди: MVP — DB-очередь, прод — Redis/брокер. Интерфейс один и тот же.


### RetryPolicy и DLQ

### Как работает retry

1. Worker берёт задачу и помечает processing.
2. При ошибке задача получает attempts+1.
3. Если attempts < лимита — ставится next\_retry\_at.
4. Backoff растущий (например 1м → 5м → 15м → 1ч).
5. После успешного выполнения — done.

 Лимиты и backoff фиксируем в Part 11 (Reliability SLA).


### Когда задача уходит в DLQ

* Исчерпан лимит attempts.
* Получена фатальная ошибка (невалидный контракт, запрет Avito, etc.).
* Сервис системно недоступен дольше допустимого SLA.

 DLQ доступен в Admin UI: причина, payload, кнопка retry.


### Идемпотентность и защита от дублей

* Каждая задача привязана к entity\_ref и имеет уникальный ключ идемпотентности.
* Worker проверяет ключ перед выполнением.
* Повторное выполнение не создаёт дубли (фото/экспорты/публикации).

 Точные ключи и правила идемпотентности описываем в Part 10 и Part 13.


### Наблюдаемость

### Operator UI видит

* Текущий статус карточки.
* Прогресс стадий (photos/export/publish).
* Человекочитаемые ошибки.

### Admin UI видит

* Очереди: глубина, скорость, ошибки.
* DLQ: упавшие задачи и причины.
* Health внешних сервисов.
* Аудит действий пользователей.

**Утверждено:**
 Тяжёлые стадии pipeline выполняются через очереди и воркеры с RetryPolicy и DLQ.
 Это обеспечивает масштабирование и гарантирует, что карточки не теряются.


**Открытые вопросы:**
 точные SLA каждой очереди, лимиты attempts и backoff — Part 11.



Part 7.
 Интеграции и контракты внешних сервисов

 Какие сервисы считаются внешними, какие данные они принимают/отдают,
 и как ядро изолируется от их реализаций.


L1 — обзор интеграционного слоя

### Принцип интеграций

 Все интеграции проходят через слой **Adapters**.
 Ядро знает только интерфейс адаптера и контракт данных.
 Это позволяет менять: auto-parser.ru → другой парсер, MinIO → Yandex S3,
 Photo API → другой маскер, Dolphin → другой антибраузер
 без переписывания доменной логики pipeline.


### Список интеграций

### Parser API

* Источник: Auto.ru через auto-parser.ru.
* Формат: JSON объявлений + raw фото.
* Режим: push/poll (настройка).

 Adapter: `ParserAdapter.php`

### Photo API (on-prem)

* Вход: raw фото + параметры маскировки.
* Выход: masked фото + метаданные.
* Режим: async через очередь photos.

 Adapter: `PhotoApiAdapter.php`

### S3 Storage (on-prem)

* Пути: raw/ masked/ exports/ tmp/.
* Операции: put/get/presign/list.
* Соглашение по именам файлов.

 Adapter: `S3Adapter.php`

### Dolphin Anty API

* Профили/сессии/браузеры.
* Robot использует как транспорт.
* Ядро видит только статусы и ошибки.

 Adapter: `DolphinAdapter.php`

### Robot Service

* Внутренняя реализация публикации.
* Вход: Card + ExportPackage.
* Выход: PublishResult + Avito status.

 Adapter: `RobotAdapter.php`

### Avito

* Площадка размещения.
* Контакты/поля Avito маппятся ядром.
* Статусы Avito нормализуются.

 Adapter: `AvitoAdapter.php`

### Контракты данных (L1)

| Интеграция | Вход в ядро | Выход из ядра | Где формализуем |
| --- | --- | --- | --- |
| **Parser API** | ParserPush: AutoRuAd + photos[] | ParserAck / ParserPoll | `external/parser/contracts` |
| **Photo API** | PhotoProcess: raw\_url + mask\_params | PhotoResult: masked\_url + meta | `external/photo-api/contracts` |
| **S3 Storage** | S3Put/S3Get/S3Presign | URLs + keys | `external/storage/contracts` |
| **Dolphin API** | ProfileStart / ProfileStop | ProfileStatus | `external/dolphin/contracts` |
| **Robot** | PublishRequest: Card + media | PublishResult | `backend/src/Modules/Robot` |
| **Avito** | robot→avito payload | AvitoStatus | `external/avito/contracts` |

 На L1 фиксируем только направления и типы контрактов. Точные схемы — Part 9.


### Нормализация ошибок

* Каждый адаптер преобразует ошибки внешнего сервиса в общий формат `IntegrationError`.
* Ядро принимает решения: retry / fail / DLQ независимо от источника ошибки.
* Operator UI показывает человекочитаемое сообщение, Admin UI — техдетали.

**Утверждено:**
 Все внешние сервисы изолированы через Adapters и формальные контракты.
 Ошибки приводятся к общему формату, pipeline управляет retry/DLQ независимо от сервиса.


**Открытые вопросы:**
 точные JSON-схемы, обязательность полей и версии контрактов — Part 9.



Part 8.
 API ядра и WebSocket события

 Какие интерфейсы предоставляет backend для Operator/Admin UI
 и как UI получает обновления pipeline в реальном времени.


L1 — интерфейсный слой

### Принцип интерфейсов

 Backend отдаёт фронтенду **HTTP API** для действий и запросов
 и **WS** для живых событий pipeline.
 UI никогда не “угадывает” состояние — он получает его из ядра
 и подписывается на изменения.


### Группы HTTP API (L1)

### Auth / Users

* login / refresh / logout
* me (профиль)
* users CRUD (admin)
* roles assign (admin)

 Модули: `Modules/Auth`, `Modules/Users`

### Cards (центр)

* cards.list (фильтры/поиск)
* cards.get
* cards.update
* cards.status.set
* cards.bulk

 Модуль: `Modules/Cards`

### Photos / Export / Publish

* photos.start / photos.retry
* export.create / export.download
* publish.start / publish.retry
* publish.status

 Модули: `Photos`, `Export`, `Publish`

### Admin / System

* queues.list / queues.pause / queues.resume
* dlq.list / dlq.retry / dlq.drop
* health.integrations
* logs.search
* metrics.basic

 Модуль: `Modules/Admin`

### Integrations facade

* integrations.status
* integrations.test
* integrations.config (owner)

 Источник: `Adapters/*` + `Config/endpoints.php`

### WebSocket события (L1)

 WS Event = {
 type,
 entity: "card|photo|export|publish\_job|queue\_job",
 id,
 status,
 payload?,
 ts
 }


| Event type | Кому нужно | Смысл |
| --- | --- | --- |
| **card.status.updated** | Operator / Admin | Изменение pipeline-статуса карточки. |
| **photos.progress** | Operator | Прогресс/ошибка обработки фото. |
| **export.created** | Operator / Admin | Пакет сформирован, доступна ссылка. |
| **publish.status.updated** | Operator / Admin | Статус публикации (robot/avito). |
| **queue.job.failed** | Admin | Ошибка задачи очереди, возможен retry. |
| **dlq.job.added** | Admin | Задача помещена в DLQ (фатальная). |

 Полный перечень событий, payload и версии — Part 9 (WS contracts).


### Как UI использует HTTP + WS

### Operator UI

* Списки карточек и фильтры — HTTP (polling по необходимости).
* Запуски стадий — HTTP actions.
* Прогресс стадий — WS подписки.
* Ошибки — человекочитаемые из ядра.

### Admin UI

* Очереди/DLQ/логи — HTTP.
* Живые изменения очередей — WS.
* Интеграции health — HTTP + WS on change.

 Важное правило:


* UI показывает только то состояние, которое пришло из ядра.
* WS — источник live-правды по прогрессу, HTTP — источник действий и снапшотов.

**Утверждено:**
 UI работает через HTTP API для операций и снапшотов и через WS для live-статусов pipeline.


**Открытые вопросы:**
 точные эндпоинты OpenAPI и WS-схемы фиксируем в Part 9 (API/WS Schemas).



Part 9.
 Контракты, схемы и версии данных

 Формальные правила: какие поля обязательны, как валидируем вход/выход,
 как версионируем схемы и не ломаем pipeline.


L2 — спецификация данных

### Зачем фиксировать контракты

 Pipeline живёт на пересечении внешних сервисов и внутренней логики.
 Контракты нужны, чтобы:
 **1)** ядро одинаково понимало вход от Parser,
 **2)** внешние сервисы не ломали формат незаметно,
 **3)** любые изменения проходили через версию схемы,
 а не “тихий” рефакторинг в коде.


### Семейства контрактов

### External → Core

* Parser Push/Poll
* Photo API Result
* Dolphin Profile Status
* Avito Publish Status

 Смысл: входные данные всегда валидируются до изменения Card.


### Core → External

* Photo API Process Request
* S3 Put/Get/Presign
* Dolphin Profile Start/Stop
* Robot Publish Request

 Смысл: ядро гарантирует структуру запросов к сервисам.


### Core → UI

* HTTP DTO (Cards/Photos/Exports/Publish)
* WS Events payload
* Errors (human + tech)

 UI не содержит логики догадок — только читает контракты.


### Где лежат схемы в репозитории

### External contracts

* `external/parser/contracts/*.json`
* `external/photo-api/contracts/*.json`
* `external/storage/contracts/*`
* `external/dolphin/contracts/*.json`
* `external/avito/contracts/*.json`

### Core & UI contracts

* `backend/src/Modules/*/*Schemas.php`
* `docs/api-docs/openapi.yaml`
* `docs/api-docs/ws-events.md`
* `frontend/src/shared/api/events.ts`

### Правила версионирования

* Каждая схема имеет поле `schema_version` (например, `1.0`).
* Минорная версия (`1.1`) — добавление необязательных полей.
* Мажорная версия (`2.0`) — изменение/удаление полей, ломающие совместимость.
* Ядро обязано поддерживать минимум 2 последние минорные версии входа.

Стратегия миграции версии
* Сначала добавляем поддержку новой схемы на чтение.
* Потом переводим внешнюю систему на новую схему.
* После стабилизации — удаляем старую поддержку.

### Валидация и типы ошибок

### Где валидируем

* **Вход External → Core**: на адаптере ещё до доменных модулей.
* **Внутренние DTO**: в сервисах модулей (Schemas + Validators).
* **Выход Core → External**: перед вызовом адаптера.

### Классы ошибок

* **ValidationError** — невалидный контракт (фатальная → DLQ).
* **IntegrationError** — сервис недоступен/таймаут (retry).
* **BusinessError** — правило pipeline нарушено (user-facing).

### Ключевые контракты (минимальные формы)

### ParserPush v1

 ParserPush = {
 schema\_version: "1.0",
 source: "auto\_ru",
 ads: [
 {
 source\_id,
 vehicle: { make, model, year, body, mileage },
 price: { value, currency },
 location: { city, address?, coords? },
 description?,
 photos: [
 { url, order?, meta? }
 ],
 ts
 }
 ]
 }


### PhotoProcess v1

 PhotoProcess = {
 schema\_version: "1.0",
 card\_id,
 photo\_id,
 raw\_url,
 mask\_params: { mode: "blur|plate", strength?, bbox? }
 }
 → PhotoResult = {
 schema\_version: "1.0",
 photo\_id,
 masked\_url,
 status: "ok|error",
 error?
 }


### PublishRequest v1

 PublishRequest = {
 schema\_version: "1.0",
 publish\_job\_id,
 card: { id, fields..., photos\_masked[] },
 channel: { dolphin\_profile\_id, avito\_account\_id },
 options: { dry\_run? }
 }
 → PublishResult = {
 schema\_version: "1.0",
 publish\_job\_id,
 avito\_item\_id?,
 status: "published|failed|in\_progress",
 error?
 }


### WS Event v1

 WsEvent = {
 schema\_version: "1.0",
 type,
 entity,
 id,
 status,
 payload?,
 ts
 }


 Полные JSON-схемы с обязательностью/enum/форматами лежат в `external/*/contracts`.


### Совместимость и тесты контрактов

* Каждый адаптер имеет unit-тесты на валидные/невалидные payload.
* Fixtures внешних контрактов хранятся рядом со схемами.
* Интеграционные тесты проверяют полные цепочки: parser → card → photos → export → publish.

**Утверждено:**
 Контракты фиксируются как JSON-схемы, версии обязательны,
 вход валидируется на адаптерах, ошибки нормализуются в 3 класса.


**Открытые вопросы:**
 точный полный список полей Auto.ru и Avito-мэппинг — уточняем при первом реальном payload.



Part 10.
 State Machine карточки и переходы pipeline

 Полная логика жизненного цикла Card: какие есть статусы, кто их меняет,
 какие события их переводят и где происходят ретраи/ошибки.


L2/L3 — детальная механика

### Зачем фиксировать State Machine

 State Machine — это единый контракт “как живёт карточка”.
 Он защищает от хаоса в статусах, делает pipeline предсказуемым,
 задаёт правила retry/DLQ и позволяет фронтенду показывать правильные действия
 для каждого состояния.


### Группы статусов

### Core статусы Card

* **draft** — создана из Parser, черновик.
* **ready\_for\_photos** — ожидает фото-пайплайн.
* **ready\_for\_export** — проверена оператором.
* **ready\_for\_publish** — экспорт есть, можно публиковать.
* **published** — успешно размещена.

### Processing статусы

* **photos\_processing**
* **exporting**
* **publishing**
* **syncing\_status** — обновление статуса робота/Avito.

 Эти статусы выставляет система/воркеры.


### Error/Terminal статусы

* **photos\_failed**
* **export\_failed**
* **publish\_failed**
* **blocked** — ручная блокировка админом.

 Ошибка может быть временной (retry) или фатальной (DLQ).


### Основной поток (Happy Path)

 draft
 → photos\_processing
 → photos\_ready
 → ready\_for\_export
 → exporting
 → exported
 → ready\_for\_publish
 → publishing
 → published


 “photos\_ready” и “exported” — промежуточные доменные подтверждения завершения стадий.


### Таблица переходов Card (L2)

| Откуда | Событие | Кто инициатор | Guard-условие | Куда |
| --- | --- | --- | --- | --- |
| — | **parser.push.accepted** | ParserWorker | валидный ParserPush | **draft** |
| draft | **photos.start** | System / Operator | есть raw фото | **photos\_processing** |
| photos\_processing | **photos.done** | PhotoWorker | все фото masked ok | **photos\_ready** |
| photos\_processing | **photos.failed** | PhotoWorker | attempts исчерпаны | **photos\_failed** |
| photos\_ready | **operator.review.accept** | Operator UI | валидация карточки ok | **ready\_for\_export** |
| ready\_for\_export | **export.start** | System / Operator | карточка не заблокирована | **exporting** |
| exporting | **export.done** | ExportWorker | пакет создан | **exported** |
| exporting | **export.failed** | ExportWorker | attempts исчерпаны | **export\_failed** |
| exported | **publish.prepare** | System | есть экспортный файл | **ready\_for\_publish** |
| ready\_for\_publish | **publish.start** | System / Operator | есть dolphin\_profile | **publishing** |
| publishing | **publish.done** | PublishWorker/Robot | Avito status ok | **published** |
| publishing | **publish.failed** | PublishWorker/Robot | attempts исчерпаны | **publish\_failed** |
| * | **admin.block** | Admin UI | роль admin+ | **blocked** |
| blocked | **admin.unblock** | Admin UI | роль admin+ | предыдущий статус |

### Обработка ошибок в State Machine (L3)

### Временные ошибки (retry)

* IntegrationError: таймаут, 5xx, сетевые сбои.
* Task остаётся в processing → retrying.
* Card статус не меняется на failed до исчерпания attempts.

### Фатальные ошибки (DLQ)

* ValidationError: невалидный контракт.
* BusinessError: запрещённое состояние (например publish без экспортов).
* Задача уходит в DLQ, Card фиксируется в *\_failed.

Правило “Card не прыгает назад сама”
* Автоматический откат статуса запрещён.
* Назад переводит только оператор/админ через явное действие.
* Это сохраняет прозрачность аудита.

### Как State Machine управляет UI

* Operator UI показывает действия только для разрешённых статусов.
* Например: кнопка “Запустить фото” видна только в draft/ready\_for\_photos.
* Admin UI видит все статусы и может делать unblock/retry.
* WS-события “card.status.updated” — единственный триггер обновления UI.

**Утверждено:**
 Статусы Card формально описаны, переходы происходят только через события,
 ретраи не переводят Card в failed до исчерпания attempts, фатальные ошибки → DLQ.


**Открытые вопросы:**
 конкретные enum’ы ошибок Avito/Dolphin/Photo API и их маппинг на retry vs fatal — Part 11.



Part 11.
 Reliability, SLA и эксплуатационные правила

 Набор правил, который делает pipeline предсказуемым в проде:
 таймауты, ретраи, лимиты, health-индикаторы и порядок восстановления.


L2 — надёжность и прод-контур

### Цель Reliability слоя

 Надёжность в Autocontent — это не “чтобы не падало”, а чтобы:
 **1)** карточки всегда доходили до финала,
 **2)** ошибки были заметны и управляемы,
 **3)** время прохождения стадий было контролируемо.


### SLA на стадии pipeline (целевые значения)

| Стадия | Цель по времени | Считаем “просрочено” | Действия системы |
| --- | --- | --- | --- |
| **Parser → draft** | до 2 мин после пуша | > 10 мин | Retry parser job → DLQ при фатале |
| **Photos** | до 15 мин / карточка | > 60 мин | Backoff + auto retry → DLQ |
| **Export** | до 5 мин / пакет | > 20 мин | Retry export job → DLQ |
| **Publish** | до 10 мин / карточка | > 45 мин | Retry publish job → DLQ |
| **Status sync** | каждые 1–3 мин | > 10 мин без апдейта | Повторный опрос → алерт админу |

 Это целевые значения L2. Точные цифры можно корректировать по факту реальной нагрузки.


### RetryPolicy (L2)

### Лимиты attempts

* **parser** — 3 попытки
* **photos** — 5 попыток
* **export** — 3 попытки
* **publish** — 5 попыток
* **robot\_status** — бесконечно, но с алертом

### Backoff схема

 retry\_delays = [1m, 5m, 15m, 60m, 3h]


 При каждом ретрае берём следующий delay.
 Последний delay повторяем до исчерпания attempts.


Что считается временной ошибкой (retry)
* HTTP 429 / rate limit.
* HTTP 5xx или сетевой таймаут.
* Падение процесса Photo API / Storage / Dolphin.

Что считается фатальной ошибкой (DLQ)
* Невалидная схема входа (ValidationError).
* Отказ Avito по бизнес-причинам (запрещённое поле, бан, лимиты аккаунта).
* Несовместимая версия схемы.
* Нарушение guard-условий state machine.

### Таймауты внешних сервисов

* **Parser API**: connect 5s / read 30s
* **Photo API**: connect 5s / read 120s (фото тяжёлые)
* **S3 Storage**: connect 3s / read 30s / upload 120s
* **Dolphin API**: connect 5s / read 30s
* **Robot**: connect 5s / read 180s (публикация)

### Health-check модель

### System health

* DB доступна
* Queue backend доступен
* Workers живы
* WS сервер жив

### Integrations health

* Parser API
* Photo API
* S3 Storage
* Dolphin API
* Avito доступность (через Robot)

### Pipeline health

* глубина очередей
* доля retry
* рост DLQ
* просрок SLA

 Health выводится в Admin UI и отдаётся endpoint’ом `/health`.


### Алерты (что кричит админу)

| Сигнал | Порог | Куда | Смысл |
| --- | --- | --- | --- |
| **DLQ рост** | > 5 задач / 15 мин | Admin UI + чат | Появилась фатальная проблема |
| **Очередь фоток** | > 500 задач | Admin UI | Не хватает PhotoWorker или API тормозит |
| **SLA просрок** | > 10% карточек | Admin UI + owner | Конвейер “плывёт” по времени |
| **Integration down** | health=fail > 5 мин | Admin UI | Сервис недоступен |

### Runbooks (порядок восстановления)

### Если упал внешний сервис

1. Health показывает fail, очередь растёт.
2. Задачи автоматически уходят в retrying.
3. Админ устраняет проблему сервиса.
4. После восстановления — ручной retry DLQ (если есть).

### Если вырос DLQ

1. Смотрим тип ошибок (Validation / Business / Integration).
2. Если контракт — чинится адаптер/схема.
3. Если бизнес-ошибка — правим карточку/маппинг.
4. Делаем retry по выбранным задачам.

 Полные runbooks лежат в `docs/runbooks/`.


**Утверждено:**
 Операции через очередь защищены SLA, RetryPolicy с backoff и DLQ.
 В Admin UI есть health/alerts/runbooks для быстрого восстановления.


**Открытые вопросы:**
 финальные пороги алертов и реальные SLA уточняются после первых недель прод-эксплуатации.



Part 12.
 RBAC, права доступа и feature flags

 Формализуем доступы: какие права существуют, как они назначаются,
 где проверяются и как фичи включаются/выключаются без релиза.


L2 — безопасность и управление доступом

### Принцип RBAC

 Доступ к любому действию определяется не экраном, а **правом (permission)**.
 Роль — это набор прав.
 Проверка прав происходит **в backend** (истина) и дублируется
 **в frontend guards** (UX).


### Каталог прав (permissions)

### Cards

* `cards.read`
* `cards.update`
* `cards.bulk.update`
* `cards.status.set`
* `cards.block` (admin)

### Photos / Export / Publish

* `photos.start`
* `photos.retry` (admin/operator)
* `export.create`
* `export.download`
* `publish.start`
* `publish.retry` (admin)

### Admin / System

* `queues.read`
* `queues.pause`
* `queues.resume`
* `dlq.read`
* `dlq.retry`
* `logs.read`
* `health.read`

### Users / Roles / Config

* `users.read`
* `users.create`
* `users.update`
* `users.delete`
* `roles.assign`
* `integrations.config.write` (owner)
* `feature_flags.write` (owner)

### Наборы прав по ролям (L2)

| Permission group | Operator | Admin | Owner/Superadmin |
| --- | --- | --- | --- |
| **Cards** | read, update, bulk, status.set | read, status.set, block | all |
| **Photos** | start, retry | start, retry | all |
| **Export** | create, download | create, download | all |
| **Publish** | start | start, retry | all |
| **Admin/System** | — | queues.*, dlq.*, logs.read, health.read | all |
| **Users/Roles/Config** | — | users.read, roles.assign | all |

 Owner/Superadmin — это “суперроль”, включающая все permissions.


### Где проверяются права

### Backend (истина)

* `AuthMiddleware` валидирует токен.
* `RoleMiddleware` сверяет permission на endpoint.
* Доменные сервисы повторно проверяют guard-условия (state machine).
* Любое нарушение → `403 Forbidden` + audit-log.

 Файлы: `backend/src/Middlewares/*`, `Config/roles.php`

### Frontend (UX)

* `AuthGuard` защищает приватные роуты.
* `RoleGuard` скрывает экраны/действия без прав.
* Actions недоступные по правам не отображаются.
* Даже если кнопку “подделать” — backend всё равно запретит.

 Файлы: `frontend/src/shared/guards/*`

### Feature Flags

 Feature flags позволяют включать/выключать функции без правки кода.


* Флаги хранятся в `backend/src/Config/feature_flags.php` и/или в DB.
* Backend отдаёт активные флаги в `/me` или `/config`.
* Frontend включает функции через `FeatureFlagGuard`.
* Owner может менять флаги в Admin UI.

Примеры флагов

 feature\_flags = {
 "photos.auto\_start": true,
 "export.multi\_format": false,
 "publish.dry\_run": true,
 "admin.dlq\_bulk\_retry": false,
 "cards.ai\_description": false
 }


### Аудит действий по RBAC

* Любое действие, требующее permission, логируется в AuditLog.
* Запись: кто → что → над чем → результат (ok/forbidden/error).
* Admin UI может фильтровать аудит по пользователю/карточке/дате.

**Утверждено:**
 RBAC построен на permissions, роли — это наборы прав.
 Backend является источником истины, frontend guards дают UX.
 Feature flags управляют включением функций без релиза.


**Открытые вопросы:**
 нужно ли выделять отдельную роль “Reviewer/QA” с ограниченными правами на publish — решаем после MVP.



Part 13.
 Схема БД, индексация и миграции

 Как физически хранится источник правды Autocontent: таблицы, связи,
 ключевые индексы под фильтры Operator UI, и правила миграций.


L2/L3 — уровень хранения данных

### Принцип БД

 База данных хранит **метаданные и статусы**, а не бинарные файлы.
 Любая сущность, участвующая в pipeline, должна быть видима в БД,
 чтобы можно было восстановить состояние и объяснить “почему так”.


### Набор таблиц (L2)

### cards

* центральная таблица
* поля карточки
* status pipeline
* source/source\_id
* timestamps

### photos

* photo\_id
* card\_id FK
* raw\_key / masked\_key
* order
* status + error

### exports

* export\_id
* формат + версия
* storage\_key
* created\_by
* status

### export\_cards

* export\_id FK
* card\_id FK
* позиция в пакете

 связка N:N Export ↔ Cards


### publish\_jobs

* publish\_job\_id
* card\_id FK
* dolphin\_profile\_id
* avito\_item\_id
* status + error

### queue\_jobs

* job\_id
* type
* entity\_ref
* attempts + next\_retry\_at
* status + last\_error

### dlq\_jobs

* job\_id
* origin\_queue
* payload
* fatal\_reason
* created\_at

### users / roles / user\_roles

* пользователи
* роли
* назначение ролей
* hash токенов

### audit\_logs

* actor
* entity + entity\_id
* action
* before/after
* result

### Минимальные схемы таблиц (L2)

### cards

 cards(
 id PK,
 source,
 source\_id UNIQUE,
 status,
 vehicle\_make,
 vehicle\_model,
 vehicle\_year,
 vehicle\_body,
 mileage,
 vin NULL,
 price\_value,
 price\_currency,
 city,
 address NULL,
 lat NULL,
 lon NULL,
 description NULL,
 created\_by NULL,
 created\_at,
 updated\_at
 )


### photos

 photos(
 id PK,
 card\_id FK → cards.id,
 raw\_key,
 masked\_key NULL,
 sort\_order INT,
 status,
 error\_code NULL,
 error\_message NULL,
 created\_at,
 updated\_at
 )


### exports

 exports(
 id PK,
 format ENUM("xlsx","json"),
 version,
 storage\_key,
 status,
 created\_by FK → users.id,
 created\_at
 )


### publish\_jobs

 publish\_jobs(
 id PK,
 card\_id FK → cards.id,
 dolphin\_profile\_id,
 avito\_item\_id NULL,
 status,
 attempts,
 last\_error NULL,
 created\_at,
 updated\_at
 )


 Полные DDL-файлы и миграции лежат в `backend/src/DB/Migrations`.


### Индексы под Operator UI (L3)

* **cards(status)** — быстрые фильтры по стадиям.
* **cards(source, source\_id)** — идемпотентность входа Parser.
* **cards(vehicle\_make, vehicle\_model, vehicle\_year)** — фильтры по авто.
* **cards(price\_value)** — диапазоны цены.
* **cards(city)** — фильтры по локации.
* **photos(card\_id, status)** — прогресс фото-пайплайна.
* **publish\_jobs(card\_id, status)** — статусы публикаций.
* **queue\_jobs(type, status, next\_retry\_at)** — админские очереди.

Правило: индекс добавляем только под реальные фильтры
* Сначала измеряем частоту запросов UI/воркеров.
* Добавляем индекс, если запрос стабильно в топе нагрузки.
* Ревизия индексов — после первых недель прод-наблюдений.

### Правила миграций

### Формат

* Нумерация: `001_*.sql`, `002_*.sql` …
* Миграции атомарные и обратимые по смыслу.
* Каждая миграция сопровождается коротким README/комментом.

### Совместимость

* Сначала добавляем новые поля (NULL/optional).
* Потом обновляем код.
* После стабилизации — делаем tighten/NOT NULL/cleanup.

**Утверждено:**
 БД — источник правды по карточкам, статусам и очередям.
 Основная таблица — cards, индексы строятся под реальные фильтры Operator/Admin UI,
 миграции версионируются и не ломают совместимость.


**Открытые вопросы:**
 выбор конкретного движка (Postgres vs MySQL) и типы json-полей — фиксируем при старте разработки.



Part 14.
 Архитектура фронтенда (слои, apps, features)

 Как организован фронтенд Autocontent: слоистость “design → shared → features → apps”,
 как это помогает поддержке двух UI-контуров и росту функционала.


L2 — фронтенд-архитектура

### Смысл слоистой архитектуры

 Фронтенд делится на 4 уровня, чтобы:
 **1)** дизайн был единым и переиспользуемым,
 **2)** инфраструктура (API/store/guards) не дублировалась,
 **3)** доменные фичи развивались независимо,
 **4)** Operator/Admin UI собирались из одной базы, но с разными маршрутами.


### Слои фронтенда

### 1) design/ — конечный дизайн (UI-kit)

* Токены: цвета/типографика/отступы/темы.
* Примитивы: кнопки, инпуты, таблицы, модалки, бейджи.
* Лэйауты: Shell, Sidebar, Header, Grid.
* Storybook для визуального контроля.

 Не содержит бизнес-логики, только “красивые кирпичи”.


### 2) shared/ — инфраструктура

* API-клиент, endpoints, WS.
* Глобальный store + session.
* Хуки (useMe, useRole, useWSSubscription).
* Guards (Auth/Role/FeatureFlag).
* Утилиты, константы, mocks.

 Это “двигатель”, одинаковый для всех UI-контуров.


### 3) features/ — доменные блоки

* cards, photos, export, publish, parser, admin.
* Внутри фичи: api.ts, model.ts, schemas.ts, ui/*.
* Фича знает только свою доменную область.
* Фича не знает, в каком app её используют.

### 4) apps/ — 2 контура UI

* **apps/operator** — рабочие страницы оператора.
* **apps/admin** — панели очередей, DLQ, логов.
* Свой routes.tsx и каркас App.tsx у каждого контура.
* Собираются из общих features и shared.

### Как собирается экран

 Design primitives/layouts
 → Feature UI components
 → App pages
 → Routes
 → AppShell


 То есть “красивые кирпичи” → “доменные куски” → “страницы по ролям”.


### Роутинг и изоляция контуров

### Operator routes

* /dashboard
* /cards
* /photos
* /export
* /publish

### Admin routes

* /admin/queues
* /admin/dlq
* /admin/logs
* /admin/integrations
* /admin/users-roles

* Guard’ы на уровне routes скрывают чужие контуры.
* Если у пользователя 2 роли — доступно переключение app-mode.
* Общий AppShell (sidebar/header) подключается в зависимости от режима.

### Управление состоянием

### Глобальное состояние (shared/store)

* session.slice — пользователь, роли, флаги.
* cards.slice — списки карточек, фильтры, кеш.
* publish.slice — статусы публикаций.
* queue.slice — только для Admin UI.

### Локальное состояние (features)

* формы редактирования карточки
* wizard-потоки (если появятся)
* табличные UI-состояния

 Источник правды по статусам — backend; store на фронте это “кеш и проекция”.


### Реакция на WS события

* shared/ws.ts поднимает соединение после login.
* useWSSubscription подписывает фичи на нужные event types.
* События обновляют store через events.ts → slices.
* UI сразу перерисовывается, без ручного refresh.

**Утверждено:**
 Фронтенд слоистый: design → shared → features → apps.
 Два UI-контура собираются из одной базы, права и флаги управляют доступом.


**Открытые вопросы:**
 нужен ли отдельный “QA app” с ограниченным набором страниц — решаем после MVP.



Part 15.
 Правила фасада UI и дизайн-система продукта

 Единые UX/визуальные правила для Operator/Admin UI:
 как строятся экраны, где применяются цвета, серые/черные блоки, акценты и градиент.


L2 — UI/UX правила

### Смысл фасада

 Фасад UI — это не “стиль ради стиля”, а способ сделать
 **конвейер понятным**. Любое решение в дизайне отвечает на вопрос:
 “можно ли за 3 секунды понять, где карточка и что с ней делать дальше?”.


### Правила цветов

### База (≈ 85%)

* **Белый фон** — основной холст страниц.
* **Серые подложки** — логические контейнеры, вторичные зоны.
* **Черный/темно-серый текст** — основная иерархия и выделения.
* Цель: спокойное чтение, фокус на данных.

### Бренд (≈ 15%)

* **Оранжевый** — только для действий и ключевых узлов.
* **Градиент** — на hero-линии, прогресс-потоках, редких акцентах.
* **Красный** — ошибки/DLQ/фатальные состояния.
* Цель: акцент на “движении карточки”.

Где обязательно использовать оранжевый
* Primary кнопки: Start Photos / Export / Publish.
* Активный этап pipeline/stepper.
* Числовые KPI/главные метрики на Dashboard.

### Типографика и иерархия

### Заголовки

* H1 (страница): 24–28px, bold, black.
* H2 (секция): 18–20px, semibold.
* H3 (блок): 15–16px, bold.

### Текст

* Body: 14–15px, dark-gray.
* Muted: 12–13px, gray.
* Тех-текст/JSON: 12–13px mono.

### Выделения

* Логика/термины — black + weight.
* Важные вторичные подсказки — в серых callout.
* Не использовать цвет как единственный сигнал.

### Сетка и layout (общие правила)

### PageShell

* Sidebar слева — навигация по доменам pipeline.
* Header сверху — поиск, профиль, режим/роль.
* Content зона — всегда белая, с карточками-контейнерами.

### Карточки и блоки

* Любой экран — это набор “white cards”.
* Внутри — серые под-контейнеры для групп данных.
* Между блоками 8–12px (чтобы не липли).

Правила таблиц (CardsTable, LogsTable и т.д.)
* Sticky header + серый фон шапки.
* Строки на белом, hover — light-gray.
* Статусы в виде бейджей (см. ниже).

### Визуальные правила статусов

| Тип статуса | Цвет | Вид в UI | Смысл |
| --- | --- | --- | --- |
| **Processing** | оранжевый / градиент тонко | Badge + ProgressBar | Стадия идёт, показываем прогресс |
| **Ready / OK** | черный/темно-серый | Badge neutral | Карточка ждёт следующего шага |
| **Done** | темный + легкий accent-outline | Badge success-neutral | Стадия завершена |
| **Error / Failed** | красный | Badge danger + текст ошибки | Нужен retry или ручная правка |
| **DLQ** | красный + серый контейнер | DLQ row highlight | Фатальная ошибка, вмешательство админа |

### UX-правила взаимодействия

### Действия по месту

* Кнопки стадий рядом со статусом.
* Bulk-actions доступны только для совместимых статусов.
* Не прячем важное в меню “…” без нужды.

### Обратная связь

* Любое действие → toast + смена статуса.
* Долго → показываем прогресс, а не “спиннер навсегда”.
* Ошибки говорим человеческим языком.

### Ошибки

* Operator видит “что сделать”.
* Admin видит “что сломалось”.
* У каждой ошибки есть код для поиска по логам.

### Фасад Dashboard (L2)

### Operator Dashboard

* KPI по своим карточкам (draft/processing/ready/published).
* Топ ошибок “что мешает работе”.
* Быстрые фильтры “продолжить работу”.

### Admin Dashboard

* KPI по очередям (depth, rate, retries).
* DLQ рост и причины.
* Health-виджет интеграций.

**Утверждено:**
 UI строится на белой базе с активным использованием серых/черных контейнеров.
 Оранжевый и градиент — только для действий и ключевых статусов (≈15%).
 Статусы показываются через бейджи/прогресс, ошибки — через понятные сообщения.


**Открытые вопросы:**
 нужно ли вводить отдельный “ночной режим” — пока нет, решаем после MVP.



Part 16.
 Operator UI — ключевые экраны и логика

 Рабочий контур оператора: какие страницы нужны для ежедневной работы,
 что на них отображается и какие действия доступны.


L2 — фронтенд по ролям

### Цель Operator UI

 Оператор должен **быстро доводить карточки до публикации**.
 Поэтому UI заточен на:
 **1)** потоковую работу со списком,
 **2)** быструю правку карточки,
 **3)** запуск стадий pipeline,
 **4)** мгновенное понимание статуса.


### Карта страниц Operator UI

### /dashboard

* KPI по карточкам (draft / processing / ready / publish\_failed / published).
* “Очередь на работу”: последние карточки в draft/photos\_ready.
* Топ ошибок по стадиям.
* Быстрые кнопки-фильтры.

### /cards

* Главная таблица карточек.
* Фильтры по статусам, авто, цене, городу.
* Сортировка, поиск по source\_id/VIN.
* Bulk actions по совместимым статусам.

### /cards/:id

* Просмотр/редактирование карточки.
* Вкладки: Data / Photos / Export / Publish / History.
* Контекстные действия pipeline.

### /photos

* Очередь карточек с фото-стадией.
* Прогресс фото по каждой карточке.
* Кнопки retry для временных ошибок.

### /export

* Список экспортов.
* Создание пакета из выбранных карточек.
* Скачивание/export link.

### /publish

* Очередь публикаций.
* Статусы робота/Avito.
* Повторная публикация (если разрешено).

### Экран Cards List (главный рабочий)

### Что отображаем в таблице

* Основные поля: марка/модель/год/цена/город.
* Статус pipeline (badge).
* Прогресс фото/экспорта/публикации (mini-progress).
* Время последнего изменения.
* Ошибки (иконка + tooltip).

### Фильтры (L2)

* Статус: draft / photos\_ready / ready\_for\_export / publish\_failed / published.
* Марка/модель/год.
* Цена (range).
* Город.
* Источник (auto\_ru).

### Bulk Actions

* **Start Photos** — только для draft/ready\_for\_photos.
* **Mark Ready for Export** — только для photos\_ready.
* **Create Export** — только для ready\_for\_export.
* **Start Publish** — только для ready\_for\_publish.

 Если статусы смешанные — bulk action скрывается или дизейблится.


### Экран Card Details (редактор)

### Вкладка Data

* Форма полей карточки.
* Валидация на лету (schemas.ts).
* Серые секции по смыслу: Vehicle / Price / Location / Text.

### Вкладка Photos

* Галерея raw/masked.
* Сравнение “до/после”.
* Drag-sort порядка фото.
* Retry по конкретной фотке.

### Вкладка Export

* История экспортов.
* Ссылка на текущий экспортный файл.
* Статус exporting/exported.

### Вкладка Publish

* История publish\_jobs.
* Статус робота/Avito.
* Avito item id + ссылка (если есть).
* Кнопка retry при publish\_failed (если разрешено).

### Вкладка History

* Лента AuditLog по карточке.
* Кто/когда менял поля и статусы.
* Ошибки стадий и попытки retry.

### Рабочий сценарий оператора (коротко)

1. Открывает /cards с фильтром “draft”.
2. Выбирает карточки → Start Photos.
3. Ждёт photos\_ready (WS обновляет таблицу).
4. Открывает карточку → проверяет Data/Photos → Mark Ready for Export.
5. Создаёт Export по готовым карточкам.
6. После ready\_for\_publish → Start Publish.
7. Следит за published/publish\_failed.

**Утверждено:**
 Operator UI состоит из Dashboard, Cards List, Card Details и доменных очередей Photos/Export/Publish.
 Главный экран — таблица карточек с фильтрами и bulk actions по state machine.


**Открытые вопросы:**
 нужны ли дополнительные “мастера” (wizard) для массовой проверки карточек — решим после MVP.



Part 17.
 Admin UI — ключевые экраны и логика

 Системный контур админа: наблюдаемость и управление pipeline,
 интеграциями, пользователями и аварийными сценариями.


L2 — системные экраны

### Цель Admin UI

 Admin UI нужен не для “работы с карточками”, а для
 **поддержания здоровья конвейера**.
 Админ должен быстро видеть: где узкое место, что упало, почему,
 и иметь безопасные рычаги восстановления.


### Карта страниц Admin UI

### /admin/dashboard

* KPI pipeline: throughput, retries%, DLQ growth.
* Глубина очередей по типам.
* Сводка health интеграций.
* Топ причин ошибок за сутки.

### /admin/queues

* Список очередей (photos/export/publish/parser/status).
* Depth, rate, avg latency.
* Фильтр по status queued/retrying/processing.
* Pause/Resume конкретной очереди.

### /admin/dlq

* Все фатальные задачи.
* Причина, payload, attempts, сервис-источник.
* Retry / Bulk retry / Drop.
* Ссылки на затронутые Cards.

### /admin/logs

* Поиск по audit\_logs и system\_logs.
* Фильтры: user, card\_id, action, time range.
* Склейка событий по correlation\_id.

### /admin/integrations

* Health всех внешних сервисов.
* Последний ping / latency.
* Отображение текущих endpoints.
* Test-call (owner).

### /admin/users-roles

* Список пользователей.
* Назначение/снятие ролей.
* Блокировка аккаунта.
* Просмотр истории действий.

### Экран Queues (центральный для админа)

### Что видно

* Очередь → depth (сколько задач).
* Rate (задач/мин).
* Средняя задержка (latency).
* Retrying %.
* Последняя ошибка.

### Что можно делать

* pause/resume очередь.
* просмотреть задачи в разрезе status.
* перевести конкретную задачу в retry сейчас.
* перепривязать задачу к Card (крайний случай).

 Важно:


* pause — только безопасный стоп (не убиваем processing).
* resume — поднимает воркеров/разрешает бэкенду брать новые задачи.
* Любое действие пишет audit-log.

### Экран DLQ (разбор фаталов)

### Столбцы DLQ

* origin\_queue
* entity\_ref (card/photo/export/publish\_job)
* fatal\_reason (Validation/Business/Integration)
* last\_error\_code
* created\_at

### Функции DLQ

* **Open payload** (сырой JSON).
* **Open card** → переход в Card Details.
* **Retry** — вернуть в origin\_queue.
* **Bulk retry** — по фильтру причины.
* **Drop** — удалить задачу (только owner).

### Экран Integrations

| Сервис | Health | Latency | Последний успех | Действия |
| --- | --- | --- | --- | --- |
| **Parser API** | ok/fail | ms | ts | test, view endpoint |
| **Photo API** | ok/fail | ms | ts | test, view endpoint |
| **S3 Storage** | ok/fail | ms | ts | test, buckets |
| **Dolphin API** | ok/fail | ms | ts | test profiles |
| **Avito (via Robot)** | ok/fail | ms | ts | test publish dry-run |

 Изменение endpoints и чувствительных параметров доступно только Owner/Superadmin.


### Экран Users & Roles

* Список пользователей с ролями и статусом аккаунта.
* Назначение роли = добавление permission-set.
* Снятие роли не удаляет историю действий.
* Блокировка аккаунта запрещает login и любые новые действия.

### Рабочий сценарий админа (коротко)

1. Смотрит /admin/dashboard: есть ли рост DLQ или очередей.
2. Если очередь растёт — проверяет Integrations health и workers.
3. Если DLQ растёт — открывает DLQ, сортирует по fatal\_reason.
4. Чинит причину (контракт/сервис/маппинг) и делает retry.
5. Следит за нормализацией rate/latency.

**Утверждено:**
 Admin UI включает dashboard, queues, DLQ, logs, integrations, users-roles.
 Главные функции — наблюдаемость pipeline и безопасное восстановление через retry/DLQ.


**Открытые вопросы:**
 нужно ли выносить отдельный экран “Workers fleet” (сколько воркеров и их состояние) — по итогам MVP.



Part 18.
 Инфраструктура, деплой и окружения

 Как Autocontent разворачивается и работает в dev/stage/prod:
 контейнеры, сети, конфиги, секреты, автозапуск и эксплуатация.


L2 — инфраструктурный контур

### Принцип окружений

 У системы 3 режима: **dev** (локальная разработка),
 **stage** (предпрод для тестов интеграций),
 **prod** (боевой).
 Во всех трёх режимах структура контейнеров едина — отличаются только конфиги и секреты.


### Контейнеры и сервисы

### backend

* PHP ядро + HTTP API
* WS сервер
* миграции БД

 Dockerfile: `infra/docker/backend.Dockerfile`

### workers

* PhotoWorker
* ExportWorker
* PublishWorker
* ParserWorker
* RobotStatusWorker

 Dockerfile: `infra/docker/workers.Dockerfile`

### frontend

* React Operator/Admin apps
* Статическая сборка
* Раздаётся через nginx

 Dockerfile: `infra/docker/frontend.Dockerfile`

### db

* Postgres/MySQL
* source of truth
* резервные копии

### queue backend

* MVP: DB очередь
* Prod: Redis/Broker
* health + metrics

### external stack

* Photo API (on-prem)
* MinIO/S3 storage
* Parser API (внешний)
* Dolphin/Avito (внешние)

 Локально может подниматься частично.


### Docker Compose

### dev compose

* `docker-compose.yml`
* hot-reload для backend/frontend
* точные эндпоинты на локальные external сервисы

### prod compose

* `docker-compose.prod.yml`
* без dev-томов и hot-reload
* production nginx + TLS

 docker compose up -d backend workers frontend db redis minio


### Kubernetes (если уходим в кластер)

### Манифесты

* `infra/k8s/backend.yaml`
* `infra/k8s/workers.yaml`
* `infra/k8s/frontend.yaml`
* `infra/k8s/external.yaml`
* `infra/k8s/ingress.yaml`

### Скалирование

* backend — 2+ реплики
* workers — горизонтально по очередям
* frontend — 2+ реплики
* autoscaling по CPU + depth queue (опционально)

### Конфиги и секреты

### Что лежит в .env

* DB\_DSN / DB\_USER / DB\_PASS
* QUEUE\_DRIVER (db|redis)
* PARSER\_ENDPOINT
* PHOTO\_API\_ENDPOINT
* S3\_ENDPOINT / S3\_KEYS
* DOLPHIN\_ENDPOINT / TOKEN
* AVITO\_ACCOUNT\_IDS
* WS\_PUBLIC\_URL

 Пример: `.env.example`

### Правила секретов

* Секреты не коммитим.
* В проде — только Secret Manager/K8s Secrets.
* Ротация ключей Dolphin/Avito/S3 — по процедуре owner.
* Любая смена ключа логируется (audit).

### Nginx и маршрутизация

* `infra/nginx/nginx.dev.conf` — прокси на backend + WS + frontend dev.
* `infra/nginx/nginx.prod.conf` — TLS, gzip, cache для статики.
* Единая точка входа: `/api/*` → backend, остальное → frontend.
* WS: `/ws` → backend WS сервер.

### Автозапуск / удобство локально

### autostart.js

* Поднимает docker compose.
* Ждёт DB/Redis/MinIO.
* Запускает миграции.
* Стартует workers.

### run\_app.bat

* Windows-обёртка для автозапуска.
* Снижает входной порог для команды.

**Утверждено:**
 Деплой идёт через Docker Compose (dev/prod) с возможностью перехода на K8s.
 Контейнеры: backend, workers, frontend, db, queue backend, external stack.
 Конфиги через .env, секреты не коммитятся и ротируются по процедуре.


**Открытые вопросы:**
 схема бэкапов DB/Storage и disaster-recovery план — Part 19.



Part 18.
 Инфраструктура, деплой и окружения

 Как Autocontent разворачивается и работает в dev/stage/prod:
 контейнеры, сети, конфиги, секреты, автозапуск и эксплуатация.


L2 — инфраструктурный контур

### Принцип окружений

 У системы 3 режима: **dev** (локальная разработка),
 **stage** (предпрод для тестов интеграций),
 **prod** (боевой).
 Во всех трёх режимах структура контейнеров едина — отличаются только конфиги и секреты.


### Контейнеры и сервисы

### backend

* PHP ядро + HTTP API
* WS сервер
* миграции БД

 Dockerfile: `infra/docker/backend.Dockerfile`

### workers

* PhotoWorker
* ExportWorker
* PublishWorker
* ParserWorker
* RobotStatusWorker

 Dockerfile: `infra/docker/workers.Dockerfile`

### frontend

* React Operator/Admin apps
* Статическая сборка
* Раздаётся через nginx

 Dockerfile: `infra/docker/frontend.Dockerfile`

### db

* Postgres/MySQL
* source of truth
* резервные копии

### queue backend

* MVP: DB очередь
* Prod: Redis/Broker
* health + metrics

### external stack

* Photo API (on-prem)
* MinIO/S3 storage
* Parser API (внешний)
* Dolphin/Avito (внешние)

 Локально может подниматься частично.


### Docker Compose

### dev compose

* `docker-compose.yml`
* hot-reload для backend/frontend
* точные эндпоинты на локальные external сервисы

### prod compose

* `docker-compose.prod.yml`
* без dev-томов и hot-reload
* production nginx + TLS

 docker compose up -d backend workers frontend db redis minio


### Kubernetes (если уходим в кластер)

### Манифесты

* `infra/k8s/backend.yaml`
* `infra/k8s/workers.yaml`
* `infra/k8s/frontend.yaml`
* `infra/k8s/external.yaml`
* `infra/k8s/ingress.yaml`

### Скалирование

* backend — 2+ реплики
* workers — горизонтально по очередям
* frontend — 2+ реплики
* autoscaling по CPU + depth queue (опционально)

### Конфиги и секреты

### Что лежит в .env

* DB\_DSN / DB\_USER / DB\_PASS
* QUEUE\_DRIVER (db|redis)
* PARSER\_ENDPOINT
* PHOTO\_API\_ENDPOINT
* S3\_ENDPOINT / S3\_KEYS
* DOLPHIN\_ENDPOINT / TOKEN
* AVITO\_ACCOUNT\_IDS
* WS\_PUBLIC\_URL

 Пример: `.env.example`

### Правила секретов

* Секреты не коммитим.
* В проде — только Secret Manager/K8s Secrets.
* Ротация ключей Dolphin/Avito/S3 — по процедуре owner.
* Любая смена ключа логируется (audit).

### Nginx и маршрутизация

* `infra/nginx/nginx.dev.conf` — прокси на backend + WS + frontend dev.
* `infra/nginx/nginx.prod.conf` — TLS, gzip, cache для статики.
* Единая точка входа: `/api/*` → backend, остальное → frontend.
* WS: `/ws` → backend WS сервер.

### Автозапуск / удобство локально

### autostart.js

* Поднимает docker compose.
* Ждёт DB/Redis/MinIO.
* Запускает миграции.
* Стартует workers.

### run\_app.bat

* Windows-обёртка для автозапуска.
* Снижает входной порог для команды.

**Утверждено:**
 Деплой идёт через Docker Compose (dev/prod) с возможностью перехода на K8s.
 Контейнеры: backend, workers, frontend, db, queue backend, external stack.
 Конфиги через .env, секреты не коммитятся и ротируются по процедуре.


**Открытые вопросы:**
 схема бэкапов DB/Storage и disaster-recovery план — Part 19.



Part 19.
 Monitoring, бэкапы и Disaster Recovery

 Как система наблюдается в проде, что именно резервируется и
 как восстанавливаемся при сбоях DB/Storage/интеграций.


L2 — эксплуатация и устойчивость

### Принцип “восстанавливаемости”

 Autocontent считается устойчивым, если после любого сбоя
 мы можем восстановить **статусы pipeline**,
 **историю действий** и **файлы**,
 не создавая дублей карточек и публикаций.


### Monitoring (что наблюдаем)

### Системные метрики

* CPU / RAM / Disk контейнеров.
* Аптайм backend/WS/workers.
* Доступность DB/Redis/MinIO.

### Pipeline метрики

* Throughput (cards/hour).
* Depth очередей по типам.
* Retry % и среднее attempts.
* DLQ growth rate.
* SLA latency по стадиям.

### Интеграционные метрики

* Health ok/fail.
* Latency внешних сервисов.
* Коды ошибок по сервисам.
* Rate-limit/429 частота.

 Метрики отображаются на Admin Dashboard и доступны через `/metrics` (если подключим Prometheus).


### Логи

### System logs

* ошибки адаптеров и воркеров
* stack traces
* latency + correlation\_id
* уровни: debug/info/warn/error

### Audit logs

* все действия пользователей
* изменения статусов
* админские операции (queues/DLQ)
* снапшоты before/after

 Обязательные поля любого лога:


 { ts, level, service, module, action, entity\_ref?, correlation\_id?, message, details? }


### Бэкапы (что и как резервируем)

| Объект | Частота | Глубина хранения | Критичность |
| --- | --- | --- | --- |
| **DB** | каждые 6 часов + daily snapshot | 30 дней | максимальная |
| **S3 Storage** | daily sync + versioning | 14–30 дней | высокая |
| **.env / configs** | при изменении | вечно (git/secret vault) | высокая |
| **Contracts / schemas** | как часть репозитория | вечно | средняя |

Почему DB важнее Storage
* DB содержит статусы pipeline и связи сущностей — это “карта мира”.
* Storage можно пересоздать частично, если есть ссылки и raw источники.

### Disaster Recovery сценарии

### 1) Потеря DB

1. Поднимаем новый инстанс DB.
2. Восстанавливаем последний snapshot + incrementals.
3. Сверяем версии миграций.
4. Включаем backend в режим “read-only check”.
5. Resume очереди.

 Возможные потери — максимум 6 часов данных (по частоте бэкапа).


### 2) Потеря Storage / MinIO

1. Поднимаем новый storage.
2. Восстанавливаем bucket’ы из daily sync.
3. Если часть raw потеряна — догружаем из Parser API (если возможно).
4. Перепроверяем ключи в DB.

 При несовпадениях — Cards в photos\_failed, оператор решает вручную.


### 3) Падение очередей/Redis

1. Resume/поднять redis/broker.
2. Если broker потерял задачи — пересоздаём из DB-источника.
3. Запускаем workers.

 DB хранит queue\_jobs, поэтому восстановление без потерь.


### 4) Падение внешней интеграции

1. Health → fail, очередь растёт.
2. RetryPolicy удерживает задачи.
3. После восстановления сервиса — ретраи продолжаются.
4. DLQ разбирается админом.

### DR чек-лист (что всегда должно быть под рукой)

* Последние snapshot DB + ключи доступа к хранилищу бэкапов.
* Список endpoints внешних сервисов (Config/endpoints.php).
* Текущие секреты Dolphin/Avito/S3 в vault.
* Runbooks для DLQ/queues.
* Контакты ответственных владельцев интеграций.

**Утверждено:**
 Наблюдаем системные/пайплайн/интеграционные метрики,
 регулярно бэкапим DB и Storage, DR-процедуры опираются на DB как карту pipeline.


**Открытые вопросы:**
 где физически хранить бэкапы (второй DC/облако) — решается с инфраструктурой.



Part 20.
 Тестирование, CI/CD и контроль качества

 Как мы гарантируем корректность Autocontent: уровни тестов,
 что запускается в GitHub Actions, как устроены релизы и проверки контрактов.


L2 — качество и поставка

### Принцип контроля качества

 Pipeline сложный и асинхронный, поэтому качество держим не “ручными проверками”,
 а **тремя уровнями тестов** + **контрактными проверками** +
 **автоматическими релизами**.


### Пирамида тестов

### Unit tests

* Тестируем отдельные модули ядра.
* StateMachine, Validators, Services, Adapters.
* Без реальных внешних API.

 Папка: `tests/unit/backend`, `tests/unit/frontend`

### Integration tests

* Тестируем связки модулей.
* Parser → Cards → Photos → Export → Publish.
* Используем fixtures контрактов.

 Папка: `tests/integration`

### E2E tests

* Прогонка полного цикла как пользователь.
* UI + backend + очередь + воркеры.
* Проверяем важные сценарии.

 Папка: `tests/e2e`

### Контрактные тесты

* Каждый контракт в `external/*/contracts` имеет fixtures.
* Adapters прогоняют fixtures на валидность JSON-схемы.
* Если схема изменилась — тест падает, пока не обновили адаптер и версию.

Минимальные проверки
* schema\_version присутствует и поддерживается.
* обязательные поля не пустые.
* enum значения корректны.
* форматы url/date/number валидны.

### CI пайплайн (GitHub Actions)

### ci.yml

1. Install deps (backend composer, frontend npm).
2. Lint/format (php-cs-fixer/eslint/prettier).
3. Unit tests (backend + frontend).
4. Integration tests (с поднятым compose).
5. Contract tests.
6. Build docker images (test mode).

### Fail-fast правила

* Любой невалидный контракт — стоп сборки.
* Падение unit — стоп, без integration.
* Coverage ниже порога — стоп.

### Release пайплайн

### release.yml

1. Tag/release в main.
2. Build prod images.
3. Push в registry.
4. Deploy в prod (compose/k8s).
5. Smoke tests /health + critical endpoints.

### Версионирование релиза

* SemVer: `MAJOR.MINOR.PATCH`
* PATCH — багфикс без изменения контрактов.
* MINOR — новая функциональность, совместимая по контрактам.
* MAJOR — ломающие изменения (контракты/SM/DB).

### Quality gates (что не даёт сломать репу)

* PR требует зелёный CI.
* Запрещены прямые пуши в main (только через PR).
* Обязателен review от владельца домена (backend/frontend/infra).
* Любые изменения контрактов требуют bump версии + обновление fixtures.

**Утверждено:**
 Качество держим через unit/integration/e2e + контрактные тесты.
 CI в PR гоняет линтеры и тесты, релизы версионируются по SemVer,
 main защищён quality gates.


**Открытые вопросы:**
 точные пороги coverage и набор smoke-сценариев — фиксируем при запуске MVP.



Part 21.
 MVP-объем, roadmap и порядок реализации

 Фиксируем “ограниченную, но гибкую” версию Autocontent:
 что точно входит в первый релиз, что откладываем и в какой очереди строим.


L2 — план поставки

### Принцип MVP

 MVP — это **полный рабочий цикл pipeline** без украшательств,
 но с надежностью и наблюдаемостью.
 Мы делаем минимальный набор, который уже даёт бизнес-ценность:
 “спарсили → обработали фото → экспорт → публикация → статусы в UI”.


### Что входит в MVP (обязательно)

### Core / Backend

* Cards domain + StateMachine.
* ParserAdapter (auto-parser.ru).
* PhotoApiAdapter + S3Adapter (MinIO).
* ExportGenerator (1 формат, напр. xlsx).
* PublishOrchestrator + RobotAdapter + DolphinAdapter.
* Очереди + воркеры (DB queue или Redis).
* WS события для UI.

### Operator UI

* Dashboard KPI.
* Cards List с фильтрами и bulk actions.
* Card Details (Data/Photos/Export/Publish/History).
* Pages Photos/Export/Publish.
* WS live-статусы.

### Admin UI

* Queues (depth/rate/pause/resume).
* DLQ (list/retry/bulk retry).
* Integrations health.
* Logs (audit + system).
* Users/Roles управление.

### Маркеры готовности MVP

* Карточка проходит happy-path до published.
* Ошибки корректно уходят в retry и DLQ.
* Оператор видит прогресс стадий в реальном времени.
* Админ может восстановить pipeline через DLQ/queues.

### Что делаем после MVP (Phase 2)

### Улучшения pipeline

* Export в нескольких форматах.
* Advanced маппинг Avito полей + шаблоны.
* Распределение публикаций по аккаунтам/профилям.
* Авто-решения части ошибок (self-heal).

### Фронтенд/UX расширения

* Wizard-потоки (массовая проверка, массовый publish).
* Персональные очереди/assign карточек.
* Глубокие отчёты/аналитика.
* Ночной режим (если нужен).

### Порядок реализации (roadmap по этапам)

1. **Фаза A — ядро и модель данных**

 Cards + DB + StateMachine + базовые Validators.
2. **Фаза B — внешние интеграции входа/фото/хранилища**

 ParserAdapter → Photos pipeline → S3Adapter.
3. **Фаза C — экспорт**

 ExportGenerator + ExportWorker + UI download.
4. **Фаза D — публикация**

 PublishOrchestrator → Robot → Dolphin → Avito status sync.
5. **Фаза E — UI контуры**

 Operator UI (все домены) → Admin UI (queues/DLQ/health/logs).
6. **Фаза F — надежность и прод-контур**

 RetryPolicy, SLA, alerts, CI/CD, runbooks.

### “Заморозка структуры” и правила расширения

* Текущая структура репозитория считается **фронтиром-стандартом**.
* Новые домены добавляются **только как Modules/* + features/***.
* Интеграции добавляются через **Adapters/* + external/*/contracts**.
* Нельзя смешивать доменную логику в Adapters или UI.
* Любое расширение должно вписываться в StateMachine, иначе это отдельный домен.

Как безопасно расширять систему
* Сначала пишем контракт + fixtures.
* Дальше — адаптер/модуль/feature.
* Добавляем статусы/переходы StateMachine.
* Только потом — UI кнопки и экраны.

**Утверждено:**
 MVP = полный цикл pipeline + Operator/Admin UI + retry/DLQ/WS.
 Структуру можно считать замороженной: расширяемся только через Modules/Features/Adapters и контракты.


**Открытые вопросы:**
 точный состав Phase 2 и приоритеты уточняются после запуска MVP и замеров нагрузки.



Part 22.
 Глоссарий, термины и правила нейминга

 Единый словарь Autocontent: что значит каждый термин,
 как называем сущности, статусы, файлы и события.


L1/L2 — общий договор

### Зачем нужен глоссарий

 У системы много доменных терминов, интеграций и статусов.
 Глоссарий фиксирует “как мы называем вещи”, чтобы:
 разработчики, операторы и руководство говорили одним языком.


### Глоссарий сущностей

| Термин | Определение | Где живёт |
| --- | --- | --- |
| **Card (Карточка)** | Центральная сущность — объявление авто, которое проходит pipeline. | DB: `cards`, Backend: `Modules/Cards`, UI: `features/cards` |
| **Pipeline** | Последовательность стадий draft → photos → export → publish. | Docs Part 4–11, Backend Workers, UI statuses |
| **Stage (Стадия)** | Одна часть pipeline (Photos/Export/Publish/Status Sync). | Backend: Modules + Workers |
| **Adapter** | Интеграционный слой, скрывающий реализацию внешнего сервиса. | `backend/src/Adapters` |
| **External Service** | Внешняя или on-prem система, связанная контрактом. | `external/*` + Adapters |
| **Worker** | Фоновый consumer, обрабатывающий очередь. | `backend/src/Workers` |
| **Queue Job** | Задача в очереди для стадии pipeline. | DB: `queue_jobs`, Backend: `Queues/*` |
| **DLQ Job** | Фатальная задача, требующая вмешательства админа. | DB: `dlq_jobs`, Admin UI |
| **Export Package** | Сформированный файл/пакет карточек для передачи дальше. | DB: `exports`, Storage: `exports/` |
| **Publish Job** | Одна попытка публикации карточки роботом в Avito. | DB: `publish_jobs`, Backend: `Modules/Publish` |
| **WS Event** | Событие реального времени о прогрессе pipeline. | `backend/src/WS`, UI: `shared/api/ws.ts` |

### Правила нейминга (обязательные)

### Backend

* Модуль = домен: `Modules/DomainName`.
* Файлы: `DomainController.php`, `DomainService.php`, `DomainModel.php`.
* Схемы/валидаторы: `DomainSchemas.php`, `DomainValidators.php`.
* Adapters: `XxxAdapter.php` (1 сервис = 1 адаптер).
* Workers: `XxxWorker.php` по названию стадии.

### Frontend

* Feature = домен: `features/domain`.
* Внутри: `api.ts`, `model.ts`, `schemas.ts`, `ui/*`.
* Компоненты: PascalCase, файл = компонент.
* Хуки: `useXxx.ts`.
* Guards: `XxxGuard.tsx`.

### Contracts / Schemas

* JSON schema файл: `service.action.schema.json`
* Fixtures: рядом, с тем же префиксом.
* Обязательное поле: `schema_version`.
* Любое изменение схемы = bump версии + обновление fixtures + адаптеров.

### Нейминг статусов и событий

### Card statuses

* snake\_case
* префикс по стадии: `photos_*`, `export_*`, `publish_*`
* терминальные: `published`, `*_failed`, `blocked`

### WS/HTTP events

* dot notation: `domain.action.state`
* пример: `card.status.updated`, `photos.progress`
* payload всегда версии

### Как ориентироваться в репозитории

 backend/ → ядро, API, workers, state machine
 external/ → контракты внешних сервисов + on-prem реализации
 frontend/ → design/shared/features/apps
 infra/ → docker/nginx/k8s, деплой
 docs/ → архитектура, openapi, runbooks
 tests/ → unit/integration/e2e/fixtures/mocks


 Если не знаешь, куда класть код — отвечай на вопрос:
 “это домен?” → Modules/Features,
 “это внешняя интеграция?” → Adapters + external/contracts,
 “это инфраструктура?” → infra.


**Утверждено:**
 Термины и нейминг зафиксированы.
 Любое расширение обязано соблюдать: домены в Modules/Features, интеграции в Adapters + contracts,
 статусы snake\_case, события dot-notation.



Part 23.
 Правила работы с репозиторием (CONTRIBUTING)

 Набор обязательных правил для команды, чтобы структура оставалась “замороженной”,
 а расширения были безопасными и предсказуемыми.


L1/L2 — командный процесс

### Главный принцип

 Репозиторий — это контракт.
 Мы не “двигаем папки как удобно сегодня”, мы развиваем систему
 через заранее утверждённые точки расширения.


### Ветки и базовый flow

### Основные ветки

* **main** — только стабильные релизы.
* **develop** — интеграционная ветка (если нужна).
* **feature/*** — работа над фичей/доменом.
* **fix/*** — багфиксы.

### Pull Request правила

* PR маленький, в пределах одной фичи.
* Обязательно описание “что/зачем/как проверить”.
* CI должен быть зелёным.
* Review от владельца домена.

### Куда класть новый код (решающее дерево)

 Если это бизнес-логика домена →
 backend/src/Modules/ +
 frontend/src/features/

 Если это интеграция с сервисом →
 backend/src/Adapters/ +
 external//contracts + fixtures

 Если это инфраструктура/деплой →
 infra/ + docker-compose* + k8s*

 Если это документация →
 docs/ (architecture/api-docs/runbooks)

 Если это тесты →
 tests/unit | tests/integration | tests/e2e


### Правила “заморозки структуры”

* Запрещено переименовывать папки верхнего уровня без решения владельца проекта.
* Новые домены добавляются только через:
 `Modules/*` + `features/*` + (опционально) `Workers/*`.
* Нельзя писать доменную логику в `Adapters`.
* Нельзя писать API-вызовы напрямую из UI мимо `shared/api`.
* Любое расширение pipeline обязано обновлять:
 **StateMachine** + **contracts** + **tests**.

### Как менять контракты

1. Добавить/изменить JSON-schema в `external/*/contracts`.
2. Поднять `schema_version` (minor или major).
3. Обновить fixtures рядом со схемой.
4. Обновить соответствующий Adapter и его unit-тесты.
5. Обновить OpenAPI/WS docs, если контракт затрагивает UI.

 Контракт без версии = запрещённый контракт.


### Как менять State Machine

1. Сначала описать изменение в `docs/architecture/state_machine.md`.
2. Добавить статусы/переходы в `backend/src/Utils/StateMachine.php`.
3. Обновить доменные сервисы/воркеры.
4. Обновить UI кнопки/guards на новые статусы.
5. Добавить/обновить тесты state machine (unit/e2e).

### Definition of Done для любой фичи

### Backend DoD

* Есть схемы/валидаторы.
* Есть unit-тесты на сервисы/адаптеры.
* Есть интеграционный тест для цепочки.
* Логи и ошибки нормализованы.

### Frontend DoD

* UI использует design primitives.
* API-вызовы через shared/api.
* Guards/permissions учтены.
* WS подписки обновляют store.
* Есть unit/e2e тест на критичный сценарий.

### Сообщения коммитов

* Формат: `[domain] кратко что сделал`
* Примеры:
 `[cards] add bulk status set`,
 `[photos] fix retry backoff`,
 `[publish] map avito errors`
* Один коммит — одна мысль.

**Утверждено:**
 Структура репозитория фиксирована.
 Команда расширяет систему только через Modules/Features/Adapters/Contracts,
 соблюдая процесс изменения схем и state machine и Definition of Done.



Part 24.
 API фасад, эндпоинты и правила совместимости

 Какие REST/WS API предоставляет ядро, как они группируются по доменам,
 как их использовать во фронте и как их расширять без ломания клиентов.


L2 — API слой

### Принцип API фасада

 Backend отдаёт **единый API для обоих UI-контуров**.
 Фронт не должен “знать” про внутренние воркеры или очереди —
 он общается только через facade-endpoints и WS события.


### Группы API по доменам

### Auth

* `POST /auth/login`
* `POST /auth/logout`
* `GET /me`
* `POST /auth/refresh`

### Cards

* `GET /cards` (filters, pagination)
* `GET /cards/:id`
* `PATCH /cards/:id`
* `POST /cards/bulk`
* `POST /cards/:id/status`

### Parser

* `POST /parser/push` (внутр. вход)
* `GET /parser/health`

### Photos

* `POST /cards/:id/photos/start`
* `POST /photos/:id/retry`
* `GET /cards/:id/photos`

### Export

* `POST /exports` (create)
* `GET /exports`
* `GET /exports/:id`
* `GET /exports/:id/download`

### Publish

* `POST /publish` (start job)
* `GET /publish/jobs`
* `POST /publish/jobs/:id/retry`
* `GET /publish/jobs/:id`

### Admin: Queues

* `GET /admin/queues`
* `GET /admin/queues/:type/jobs`
* `POST /admin/queues/:type/pause`
* `POST /admin/queues/:type/resume`

### Admin: DLQ

* `GET /admin/dlq`
* `GET /admin/dlq/:id`
* `POST /admin/dlq/:id/retry`
* `POST /admin/dlq/bulk-retry`

### Admin: System

* `GET /admin/health`
* `GET /admin/logs`
* `GET /admin/users`
* `POST /admin/users/:id/roles`

 Полная спецификация: `docs/api-docs/openapi.yaml`.


### Пагинация и фильтры (Cards)

 GET /cards?status=draft,photos\_ready
 &make=toyota
 &model=camry
 &year\_from=2019
 &price\_min=1200000
 &price\_max=2500000
 &city=moscow
 &page=1
 &limit=50
 &sort=updated\_at:desc


* Фильтры соответствуют индексам БД (см. Part 13).
* limit по умолчанию 50, максимум 200.
* Любая сортировка должна быть по индексируемому полю.

### DTO правила (что отдаём в UI)

### CardDTO

* только нормализованные поля
* status + stage\_progress
* errors (human + code)
* timestamps

### JobDTO (photos/export/publish)

* job\_id, type, status
* attempts, next\_retry\_at
* last\_error (code/message)
* entity\_ref

 DTO = “проекция”, не содержащая внутренних деталей реализаций сервисов.


### Совместимость API

* API версионируется через префикс: `/api/v1/...`.
* Minor изменения: добавляем необязательные поля в DTO.
* Major изменения: новый префикс v2 и параллельная поддержка v1.
* WS события также имеют `schema_version`.

### Как добавлять новые endpoints

1. Определить домен (Modules/*).
2. Добавить Schemas/Validators/Controller/Service.
3. Прописать маршрут в `backend/src/Routes/routes.php`.
4. Обновить OpenAPI + tests.
5. Подключить в frontend через `shared/api/endpoints.ts`.

**Утверждено:**
 Backend даёт единый API фасад для UI.
 Эндпоинты группируются по доменам, фильтры карточек соответствуют индексам БД,
 совместимость держим через /v1 + schema\_version в WS.


**Открытые вопросы:**
 финальный набор endpoints для Photos/Publish может уточниться по итогам MVP.



Part 25.
 WS события и real-time протокол

 Детальный контракт событий между backend и UI: какие типы событий есть,
 когда они отправляются, какие payload несут и как фронт на них реагирует.


L2 — real-time слой

### Принцип real-time в Autocontent

 UI не делает постоянный polling на прогресс стадий.
 Источник истины — backend, а обновления приходят через WS:
  **любое изменение статуса или прогресса → событие → обновление store → UI**.


### WS канал и подключение

* Единая точка: `GET /ws` (через nginx proxy).
* Подключаемся после login и получения access token.
* Токен передаётся в query/header (dev/prod одинаково).
* При обрыве — auto-reconnect с backoff.

 ws://host/ws?token=JWT


### Общий envelope события

 {
 "event": "card.status.updated",
 "schema\_version": "1.0",
 "correlation\_id": "c-8f2a...",
 "ts": "2025-12-08T12:00:12Z",
 "payload": { ... }
 }


* **event** — тип события (dot-notation).
* **schema\_version** — версия payload.
* **correlation\_id** — склейка цепочек pipeline.
* **ts** — время генерации.
* **payload** — доменные данные.

### Каталог WS событий (L2)

| Event | Когда отправляется | Payload (ключевые поля) | Кто слушает |
| --- | --- | --- | --- |
| **card.created** | Card появилась из Parser | card\_id, source\_id, status=draft | CardsList, Dashboard |
| **card.updated** | Оператор изменил поля | card\_id, changed\_fields, updated\_at | CardDetails, CardsList |
| **card.status.updated** | Смена статуса pipeline | card\_id, from, to, stage\_progress | Все экраны, где есть Card |
| **photos.progress** | Фото-воркер двигает прогресс | card\_id, total, done, failed | PhotosPage, CardDetails |
| **export.created** | Экспорт создан/обновлён | export\_id, status, card\_ids | ExportPage, CardDetails |
| **publish.progress** | Публикация в процессе | publish\_job\_id, card\_id, step, message | PublishPage, CardDetails |
| **publish.status.updated** | Статус робота/Avito изменился | publish\_job\_id, card\_id, avito\_status | PublishPage, CardsList |
| **queue.depth.updated** | Админ-очереди обновились | queue\_type, depth, retrying | Admin Queues |
| **dlq.updated** | Изменения в DLQ | dlq\_count, last\_fatal\_reason | Admin DLQ/Dashboard |
| **health.updated** | Health интеграций/системы | service, status, latency, ts | Admin Integrations |

 Полный список и версии — `docs/api-docs/ws-events.md`.


### Примеры payload

card.status.updated

 "payload": {
 "card\_id": 4123,
 "from": "photos\_processing",
 "to": "photos\_ready",
 "stage\_progress": {
 "photos": { "total": 12, "done": 12, "failed": 0 },
 "export": null,
 "publish": null
 },
 "updated\_at": "2025-12-08T12:03:41Z"
 }


publish.status.updated

 "payload": {
 "publish\_job\_id": 98,
 "card\_id": 4123,
 "avito\_item\_id": "AV-553912",
 "avito\_status": "active",
 "robot\_status": "done",
 "updated\_at": "2025-12-08T12:12:10Z"
 }


### Как фронт обрабатывает события

### shared/api/ws.ts

* поднимает соединение
* раскидывает события по подпискам
* делает reconnect/backoff

### shared/api/events.ts

* маппит WS events → store actions
* выполняет нормализацию payload
* обновляет slices

* Features вызывают `useWSSubscription(eventType, handler)`.
* Handler должен быть идемпотентным (двойное событие не ломает UI).
* Если UI пропустил событие — при открытии экрана делаем refresh REST.

### Надёжность WS

### Реконнект

* 1s → 2s → 5s → 15s → 60s
* после восстановления — синхронизируем критичные списки REST-запросом

### Гарантии доставки

* WS “at most once”.
* Backend не хранит очередь WS событий.
* REST всегда позволяет догнать состояние.

### Как добавлять новые WS events

1. Добавить тип события в `backend/src/WS/WsEvents.php`.
2. Описать payload и версию в `docs/api-docs/ws-events.md`.
3. Добавить emitter (модуль/воркер) где меняется состояние.
4. На фронте — mapping в `shared/api/events.ts`.
5. Добавить тест на корректность payload.

**Утверждено:**
 WS — основной канал прогресса pipeline.
 События версионируются, фронт обрабатывает их идемпотентно,
 а REST остаётся опорой для догоняющей синхронизации.


**Открытые вопросы:**
 если понадобится “гарантированная доставка” — добавим event log + last\_seen\_cursor в Phase 2.



Part 26.
 Contracts-first подход и версионирование

 Правила работы с контрактами: как фиксируются схемы внешних сервисов,
 как мы ими управляем, как обновляем без остановки pipeline.


L2 — интеграционные правила

### Принцип Contracts-first

 В Autocontent контракт важнее реализации.
 Сначала описываем “какие данные и в каком виде ходят между системами”,
 и только потом пишем код.
 Это защищает от хаоса, особенно когда есть внешние API (parser, dolphin, avito).


### Что такое контракт

### Контракт = JSON schema

* Формально описывает вход/выход сервиса.
* Хранится рядом с сервисом в `external/<service>/contracts`.
* Имеет версию `schema_version`.
* Имеет fixtures (пример реальных payload).

### Контракт защищает

* Backend от неожиданных форматов.
* UI от ломания DTO/WS.
* Стабильность pipeline при деталях интеграций.

### Где у нас есть контракты

### Parser

* `parser.push.schema.json`
* `parser.poll.schema.json`
* `parser.health.json`

### Photo API

* `photo.process.schema.json`
* `photo.status.schema.json`
* `photo.health.json`

### Storage (S3)

* `s3.health.json`
* `s3.keys.md` (док)

### Dolphin

* `dolphin.profile.schema.json`
* `dolphin.health.json`

### Avito (via Robot)

* `avito.publish.schema.json`
* `avito.status.schema.json`
* `avito.health.json`

### Внутренние API

* OpenAPI: `docs/api-docs/openapi.yaml`
* WS: `docs/api-docs/ws-events.md`

### Правила версионирования контрактов

| Тип изменения | Что делаем с версией | Пример | Совместимость |
| --- | --- | --- | --- |
| **Backward-compatible** | MINOR bump (1.1 → 1.2) | добавили optional поле | старые клиенты живут |
| **Breaking change** | MAJOR bump (1.x → 2.0) | переименовали поле, изменили enum | нужен адаптер v2 |
| **Hotfix schema** | PATCH bump (1.2.0 → 1.2.1) | починили описание формата | не меняет payload |

### Как поддерживаем несколько версий

* Adapter читает `schema_version` входящего payload.
* В Adapter есть map: version → normalizer/handler.
* Если версия неизвестна → ValidationError → DLQ.
* Старые версии поддерживаются минимум 1 релизный цикл.

Пример (логика Adapter)

 switch(schema\_version){
 case "1.0": return normalizeV1(payload);
 case "1.1": return normalizeV11(payload);
 default: throw UnsupportedSchemaVersion;
 }


### Процесс обновления контракта без остановки pipeline

1. Добавляем новую схему v2 рядом со старой v1.
2. Добавляем нормализатор v2 в Adapter.
3. Деплоим backend с поддержкой v1+v2.
4. Переключаем источник (Parser/Photo/Robot) на v2.
5. Собираем статистику ошибок v2.
6. После стабилизации — помечаем v1 как deprecated.

 Главное правило: **сначала поддержка в коде, потом включение у сервиса**.


### Контрактные тесты — обязательная страховка

* Fixtures валидируются схемой на CI (см. Part 20).
* Adapter unit-тесты прогоняют fixtures и проверяют нормализацию.
* Если контракт меняется и не обновлены fixtures — CI падает.

### Кто владеет контрактами

### Внешние сервисы

* Owner интеграции (backend lead).
* Согласование изменений с тех. владельцем сервиса.
* Обязательный bump версии.

### Внутренние API

* Owner продукта + frontend lead.
* OpenAPI/WS docs — источник истины для UI.
* Breaking change = новая версия /v2.

**Утверждено:**
 Contracts-first обязателен: схемы в external/, fixtures рядом,
 версии schema\_version, multi-version support через Adapter map,
 обновление без остановки pipeline по staged-процессу.



Part 27.
 Безопасность, приватность и compliance

 Как защищаем Autocontent: аутентификация и сессии, права доступа,
 безопасная работа с внешними сервисами, хранение данных и аудит.


L2 — безопасность продукта

### Принцип безопасности

 Безопасность здесь — это прежде всего
 **контроль доступа к карточкам и к внешним площадкам**.
 Мы защищаемся не “одним барьером”, а несколькими:
 auth → RBAC → audit → изоляция секретов → ограничение действий робота.


### Аутентификация и сессии

### JWT/Session flow

* Логин по `/auth/login` → выдача access + refresh.
* access живёт коротко (минуты), refresh — дольше (дни).
* refresh можно отозвать (logout/блокировка пользователя).

### Правила хранения

* access — только в памяти приложения (не localStorage).
* refresh — httpOnly cookie или защищённое хранилище.
* При смене роли/флагов — forced refresh.

### RBAC как барьер безопасности

* Все endpoints защищены middleware (см. Part 12).
* Frontend guards — только UX, не защита.
* Каждый sensitive endpoint пишет audit-log.

### Секреты внешних сервисов

### Что считаем секретом

* Dolphin token / profile keys.
* Avito account keys / session artifacts.
* S3 access/secret keys (MinIO).
* Parser API keys.

### Как храним

* Dev — в локальном .env (не коммитим).
* Prod — Secret Vault / K8s Secrets.
* Ротация ключей по регламенту owner.
* Подмена/утечка фиксируется audit-log.

### Безопасность робота публикации

* Robot работает **только** по publish\_jobs из backend.
* Robot не имеет “свободного режима” — только заданные действия.
* Ограничение rate-limit публикаций на уровне backend.
* Dry-run режим (feature flag) для безопасных тестов.
* Все действия робота логируются с correlation\_id.

### Приватность данных

### Что храним

* Метаданные карточки (DB).
* Ссылки на фото (storage keys).
* История действий (audit\_logs).

### Что не храним

* Личные данные пользователей Avito/Dolphin.
* Внутренние cookies/профили Dolphin — не в DB.
* Сырые токены в логах.

* Фото с номерами автомобилей после маскировки не несут PII.
* Если PII появляется в payload Parser — нормализатор удаляет/маскирует.

### Сетевые ограничения

* Backend доступен только через nginx/ingress.
* CORS разрешён только для домена фронта.
* WS подключение также ограничено origin.
* В dev можно расширять origin через feature flag.

### Аудит и расследование

* AuditLog хранит каждое действие пользователя/админа.
* System logs хранят ошибки адаптеров/робота.
* По correlation\_id можно восстановить полный путь карточки.

### Минимальные требования compliance для MVP

* Секреты не в репозитории.
* RBAC включён на все endpoints.
* Аудит доступен админам.
* Ошибки без PII в логах.
* Доступ к прод-окружению только по списку.

**Утверждено:**
 Безопасность строится на auth + RBAC + audit + изоляции секретов.
 Robot работает только по заданиям backend, секреты не попадают в код/логи,
 PII не хранится в DB и маскируется на входе.



Part 28.
 Производительность и масштабирование

 Как Autocontent выдерживает рост объёма карточек: где ожидаем нагрузку,
 как ограничиваем, распараллеливаем и не теряем стабильность pipeline.


L2 — производственный контур

### Принцип производительности

 Производительность достигается не “быстрым кодом”, а правильным разрезом:
 **очереди → воркеры → идемпотентность → лимиты интеграций**.
 Мы заранее проектируем так, чтобы любой этап можно было усилить
 независимо от остальных.


### Главные точки нагрузки

### 1) Parser intake

* массовое создание Cards
* upsert/merge
* валидация payload

### 2) Photos pipeline

* скачивание raw фото
* маскировка (CPU/GPU)
* загрузка в storage

### 3) Publish pipeline

* антибот/браузерная автоматика
* rate-limit Dolphin/Avito
* долгие сессии

 Export — обычно дешевле, т.к. это CPU+IO локально и без внешних лимитов.


### Масштабирование через очереди

* Каждая стадия имеет свою очередь (`photos`, `export`, `publish`…).
* Каждый Worker масштабируется отдельно (replicas).
* Если стадия узкая — увеличиваем только её воркеры.
* Очереди дают back-pressure: вход идёт быстрее, чем выход, но система не падает.

 cards intake → photos queue → photo workers xN
 ↓
 export queue → export workers xM
 ↓
 publish queue → publish workers xK


### Идемпотентность и дедупликация

### На входе Parser

* Уникальность: `(source, source_id)`.
* Повторный payload → update карточки, не новый insert.
* Нормализатор вычищает шум/PII.

### В очередях

* Job содержит `idempotency_key`.
* Повторное задание не запускает стадию повторно, если она уже done.
* Retry не создаёт дубль publish\_jobs.

 Это ключ к устойчивости при рестартах/повторах/обрывах интеграций.


### Rate limiting внешних сервисов

### Dolphin / Avito

* Лимит публикаций на аккаунт/час.
* Лимит параллельных сессий.
* Backoff при 429/anti-bot сигналах.

### Parser / Photo API / S3

* Пакетная обработка (batching).
* Ограничение одновременных запросов.
* RetryPolicy для временных ошибок.

### Кеширование

* Frontend кеширует списки Cards через queryClient.
* Backend кеширует справочники (марки/модели/города) в памяти/redis.
* WS события инвалидируют кеш выборочно.

### Производительность БД

* Фильтры UI опираются на индексы (Part 13).
* Пагинация только keyset/offset-limit с ограничением limit.
* Тяжёлые выборки (audit/logs) — по time range и индексам.
* Транзакции короткие, без блокировок таблиц.

### Как масштабируем при росте

1. Смотрим метрики: какая очередь растёт и какое SLA нарушено.
2. Если очередь растёт из-за внешнего лимита → усиливаем backoff, а не воркеры.
3. Если очередь растёт из-за CPU/IO → добавляем replicas workers.
4. Если DB начинает быть узким местом → включаем read-replica и оптимизируем индексы.
5. Если storage узкий → переносим MinIO на отдельный узел + включаем versioning.

**Утверждено:**
 Масштабирование строится через независимые очереди и воркеры.
 Основные узкие места — intake parser, photos pipeline и publish pipeline.
 Идемпотентность и rate-limit защищают от дублей и падений интеграций.



Part 29.
 Риски, допущения и открытые решения

 Фиксируем ключевые допущения проекта, технические и операционные риски,
 и список решений, которые нужно принять до/во время MVP.


L1/L2 — управляем неопределённость

### Почему важно зафиксировать риски сейчас

 Autocontent — pipeline с внешними сервисами и роботизацией.
 Значит, главные угрозы — не “код в одном месте”, а
 **интеграции, масштаб, антибот и операционный процесс**.
 Чем раньше их обозначим, тем дешевле будет решение.


### Ключевые допущения (assumptions)

### A1. Источник данных стабилен

* auto-parser.ru продолжает отдавать валидные payload.
* Изменения формата приходят заранее (или ловятся контрактами).

### A2. On-prem сервисы под контролем

* Photo API и MinIO доступны 24/7 внутри сети.
* Есть хотя бы базовый мониторинг и бэкапы.

### A3. Dolphin/Avito лимиты известны

* Мы знаем rate-limit и правила антибота.
* Есть пул профилей/аккаунтов под нужный объём.

### A4. Операторский процесс регламентирован

* Есть роли и ответственность за DLQ/ошибки.
* Операторы понимают happy-path и retry-логику.

### Технические риски

### R1. Ломающиеся внешние форматы

* Parser или Dolphin меняют JSON.
* Риск: массовый DLQ, стоп pipeline.
* Контрмера: contracts-first + multi-version adapters.

### R2. Антибот Avito/Dolphin

* Блокировки профилей/аккаунтов.
* Риск: publish queue растёт.
* Контрмера: rate-limit, backoff, ротация профилей.

### R3. Нагрузка на Photos

* CPU/GPU узкое место.
* Риск: очередь фото копится сутками.
* Контрмера: выделенные воркеры + горизонтальное масштабирование.

### R4. Неконсистентность статусов

* Стадия упала после частичного успеха.
* Риск: карточки “застревают”.
* Контрмера: идемпотентность + audit + ручной reset админом.

### R5. Сбой on-prem storage

* MinIO/диск умер.
* Риск: потеря фото.
* Контрмера: versioning + daily backup + DR-процедура.

### R6. Слабый мониторинг

* Не замечаем деградацию вовремя.
* Риск: SLA рушится тихо.
* Контрмера: метрики + алерты + Admin dashboard.

### Операционные риски

### O1. Нет владельца DLQ

* Фаталы копятся без реакции.
* Риск: pipeline “умирает” в тишине.
* Контрмера: назначить on-call/ответственного + runbook.

### O2. Некорректная ручная правка

* Оператор меняет поля “как кажется”.
* Риск: ошибки публикации/мэппинга.
* Контрмера: валидаторы форм + подсказки UI + audit.

### Открытые решения (принять до финала MVP)

| Решение | Варианты | Влияние | Когда фиксируем |
| --- | --- | --- | --- |
| **D1. DB движок** | Postgres / MySQL | DDL, индексы, миграции | Фаза A |
| **D2. Очередь прод-уровня** | Redis Streams / RabbitMQ / DB-queue | throughput, retry | между Фазой B и F |
| **D3. Формат Export MVP** | XLSX / JSON | UI/генератор/интеграции | Фаза C |
| **D4. Лимиты publish** | по аккаунту/профилю/час | SLA публикаций | Фаза D |
| **D5. Периметр мониторинга** | Prometheus+Grafana / простые health-checks | надежность прод-контуров | Фаза F |

### Как поймём, что риски под контролем

* DLQ не растёт “вечно” — есть владелец и среднее время разбора.
* Publish-ошибки держатся в пределах ожидаемых лимитов.
* Photos-очередь стабильно опустошается быстрее, чем наполняется.
* Contracts меняются через версии и не ломают pipeline внезапно.

**Утверждено:**
 Зафиксированы ключевые допущения, тех/операционные риски и список решений,
 которые нужно принять в ходе MVP. Это часть “замороженной” архитектуры.



Part 30.
 Финальная фиксация архитектуры и резюме

 Закрепляем итог: что такое Autocontent, как работает,
 какая структура репозитория заморожена, и как по ней развиваем продукт.


L1 — финал документа

### Autocontent — что это

 Autocontent — это **конвейер автокарточек**, который автоматизирует путь:
 **Parser (Auto.ru/auto-parser.ru) → Cards → Photos → Export → Publish → Avito**
 с наблюдаемостью, ретраями и живыми статусами в UI.


 Главная ценность: меньше ручной рутины + быстрый стабильный выпуск объявлений.


### Конвейер в одном экране

 Parser(API) → Cards(draft)
 → Photos(queue) → Photo API → Storage
 → Export(queue) → Storage
 → Publish(queue) → Robot → Dolphin → Avito
 → WS статусы в UI
 → DLQ при фатальных ошибках


### Замороженная структура репозитория (итоговая)

 full\_project/
 ├── backend/ # PHP ядро, домены Modules/*, Adapters/*, Workers/*, Queues/*
 ├── external/ # контракты внешних и on-prem сервисов + fixtures
 ├── frontend/ # React: design/ shared/ features/ apps(operator/admin)
 ├── infra/ # docker/nginx/k8s + compose
 ├── docs/ # C4 модели, OpenAPI, WS events, runbooks
 ├── tests/ # unit / integration / e2e / mocks / fixtures
 └── ...root files


* Доменная логика — только в `backend/src/Modules` и `frontend/src/features`.
* Интеграции — только через `Adapters` + `external/*/contracts`.
* Pipeline меняется только через StateMachine + тесты.

### Что утверждено этим документом

### Архитектура

* State Machine карточки и стадии pipeline.
* Очереди + воркеры как базовый механизм.
* Adapters как фасад интеграций.
* WS как канал статусов.
* DLQ как механизм фаталов.

### UI и дизайн

* 2 контура UI: Operator/Admin.
* Слоистость фронта: design → shared → features → apps.
* Белая база + серые/черные блоки, акцент бренда ≈15%.
* Статусы через бейджи/прогресс.

### Инфра и качество

* Docker compose (dev/prod) + путь в k8s.
* Contracts-first и versioning.
* Unit/Integration/E2E + contract tests на CI.
* Monitoring + backup + DR процедуры.

### MVP и расширение

* MVP = полный happy-path конвейера.
* Roadmap по фазам A–F.
* Структура репы “заморожена”, расширяемся через точки расширения.

### Как по этому жить и развивать дальше

1. **Любая новая функциональность начинается с контракта.**

 Схема → fixtures → версия → только потом код.
2. **Новые домены добавляем как Modules + features.**

 Не в Adapters, не в Utils, не в UI “напрямую”.
3. **Pipeline меняем через StateMachine.**

 Статусы/переходы → модуль/воркер → UI кнопки/guards → тесты.
4. **Стабильность важнее фич.**

 Сначала надежность/наблюдаемость, потом расширения Phase 2.

### Ближайшие конкретные шаги (после утверждения)

1. Фаза A: зафиксировать DB движок и поднять базовые миграции.
2. Фаза B: подключить ParserAdapter и протянуть draft → photos\_queue.
3. Собрать Photo API on-prem pipeline и MinIO buckets.
4. Фаза C: сделать 1 формат Export и страницу export download.
5. Фаза D: сделать RobotAdapter + Dolphin + Avito publish flow.
6. Фаза E: добить Operator/Admin UI и WS live-статусы.
7. Фаза F: CI/CD, мониторинг, алерты, runbooks.

**Финал:**
 Архитектура, структура репозитория и правила расширения Autocontent
 зафиксированы и считаются “замороженными”.
 Дальше работаем строго по Contracts-first, StateMachine и утверждённым точкам расширения.



Part 31.
 Appendix A — Сквозной сценарий (happy path) в псевдокоде

 Прямая, наглядная “протяжка” всего конвейера: какие модули/воркеры/адаптеры
 участвуют на каждом шаге и какие статусы меняются.


Appendix / L2

### Зачем эта часть

 Это “операционный план” для разработчика: если читать сверху вниз,
 становится видно **что за чем вызывается** и **какие сущности рождаются**.


### Happy path: Parser → Published

 // 0) Parser push (вход)
 POST /parser/push → ParserController::push()
 validate(parser.push.schema.json)
 normalized = ParserNormalizer::fromAutoParser(payload)
 card = CardsService::upsertFromParser(normalized)
 card.status = "draft"
 WS.emit("card.created", CardDTO(card))

 // 1) Оператор запускает Photos
 POST /cards/:id/photos/start → PhotosController::start(card\_id)
 StateMachine.assert(card.status in ["draft","ready\_for\_photos"])
 job = PhotosJobs::enqueue(card\_id)
 card.status = "photos\_queued"
 WS.emit("card.status.updated", ...)

 // 2) PhotosWorker обрабатывает очередь
 PhotosWorker::handle(job)
 card = CardsModel::get(job.card\_id)
 card.status = "photos\_processing"
 WS.emit("photos.progress", {total, done:0, failed:0})

 raw\_urls = ParserModel::getRawPhotoUrls(card\_id)
 foreach(url in raw\_urls):
 file\_raw = PhotoApiAdapter::download(url)
 file\_masked = PhotoApiAdapter::maskPlate(file\_raw)
 key = S3Adapter::putMasked(file\_masked)
 PhotosModel::attach(card\_id, key)
 WS.emit("photos.progress", {done:+1})

 card.status = "photos\_ready"
 WS.emit("card.status.updated", ...)

 // 3) Оператор/система переводит в ready\_for\_export
 POST /cards/:id/status {to:"ready\_for\_export"}
 StateMachine.assert(card.status=="photos\_ready")
 card.status="ready\_for\_export"
 WS.emit("card.status.updated", ...)

 // 4) Создание экспорта
 POST /exports {card\_ids:[...]} → ExportController::create()
 foreach(id in card\_ids): assert(cards[id].status=="ready\_for\_export")
 export = ExportService::createExport(card\_ids)
 ExportJobs::enqueue(export.id)
 WS.emit("export.created", ExportDTO(export))

 // 5) ExportWorker
 ExportWorker::handle(job)
 export = ExportModel::get(job.export\_id)
 export.status="export\_processing"
 rows = ExportGenerator::build(export.card\_ids)
 file = ExportGenerator::toXlsx(rows)
 key = S3Adapter::putExport(file)
 export.file\_key = key
 export.status="exported"
 foreach(card\_id in export.card\_ids):
 CardsService::setStatus(card\_id, "ready\_for\_publish")
 WS.emit("card.status.updated", ...)
 WS.emit("export.created", ExportDTO(export))

 // 6) Публикация
 POST /publish {card\_ids:[...]} → PublishController::start()
 foreach(id): assert(cards[id].status=="ready\_for\_publish")
 foreach(id):
 job = PublishJobs::enqueue(id)
 CardsService::setStatus(id, "publish\_queued")
 WS.emit("card.status.updated", ...)

 // 7) PublishWorker
 PublishWorker::handle(job)
 card = CardsModel::get(job.card\_id)
 CardsService::setStatus(card.id, "publish\_processing")
 WS.emit("publish.progress", {step:"start"})

 profile = DolphinAdapter::allocateProfile(card)
 session = RobotAdapter::start(profile)
 avito\_payload = AvitoAdapter::map(card)
 result = RobotAdapter::publish(session, avito\_payload)

 PublishModel::markDone(job.id, result.avito\_item\_id)
 CardsService::setStatus(card.id, "published")
 WS.emit("publish.status.updated", {avito\_status:"active"})
 WS.emit("card.status.updated", ...)

 // 8) UI получает WS и обновляет store
 UI(ws.on("card.status.updated")) → cards.slice.update(...)
 UI(ws.on("photos.progress")) → photos.slice.update(...)
 UI(ws.on("publish.status.updated")) → publish.slice.update(...)


**Итог:**
 Сквозной happy-path прозрачно раскладывается на:
 Controllers → Jobs → Queues → Workers → Adapters → WS → UI store.



Part 32.
 Appendix B — Чек-листы запуска и приемки

 Практические списки: что должно быть заведено/поднято,
 и как мы принимаем MVP технически и операционно.


Appendix / L2

### Dev checklist (локальная разработка)

1. .env заполнен локальными endpoints (Parser/Photo/MinIO).
2. docker compose up поднимает backend/workers/frontend/db.
3. Миграции прогнаны, seed создал admin user.
4. WS подключается и шлёт тест-события.
5. Fixtures Parser/Photo проходят в contract tests.

### Stage checklist (предпрод)

1. Подключен реальный Parser API key.
2. Photo API поднят на сервере, health ok.
3. MinIO поднят, buckets созданы, versioning включен.
4. Dolphin токены заведены в vault, profiles готовы.
5. Robot dry-run режим включен для первых тестов.
6. Admin UI показывает health всех интеграций.

### Prod checklist (боевой запуск)

1. Секреты только через vault/Secrets (не .env в репе).
2. Мониторинг глубины очередей + алерты включены.
3. DB бэкапы по расписанию, проверено восстановление.
4. MinIO бэкапы + nightly sync в 2-е хранилище.
5. Publish rate-limit задан и протестирован.
6. Runbooks готовы (DLQ/Integrations/OnCall).
7. Ответственные роли назначены (owner/admin/operator).

### Критерии приемки MVP

* Happy-path: 50+ карточек прошли до published без ручных фиксов кода.
* Ошибки Parser/Photo/Publish корректно уходят в retry и DLQ.
* Operator UI позволяет довести карточку до готовности без админа.
* Admin UI позволяет остановить/восстановить pipeline и разобрать DLQ.
* WS статусы в UI соответствуют DB конечному состоянию.

**Итог:**
 Чек-листы фиксируют “как запускать” и “как принимать” MVP
 без расползания требований.



Part 33.
 Appendix C — Шаблоны экранов и UI-паттерны

 Набор “скелетов” экранов и повторяемых компонентов,
 которые применяются в Operator/Admin UI.


Appendix / L2

### Паттерн 1: Табличный доменный экран

### Применение

* /cards
* /photos
* /export
* /publish
* /admin/queues
* /admin/dlq

### Скелет

1. Header: title + KPI + primary action.
2. Filters row (серый контейнер).
3. Table card (white).
4. Bulk actions bar (sticky).
5. Pagination footer.

### Паттерн 2: Детальная карточка сущности

### Применение

* /cards/:id
* /exports/:id
* /publish/jobs/:id
* /admin/dlq/:id

### Скелет

1. Top bar: ID + status badges + next actions.
2. Tabs по доменам (Data/Photos/Export/Publish/History).
3. Секции внутри табов — серые подложки.
4. Правый aside: quick info + errors.

### Паттерн 3: Health/Monitoring экран

* Каждый сервис = строка с badge ok/fail.
* Latency — маленький серый текст.
* Primary action — “Test call”.
* Ошибки раскрываются в panel справа.

### Повторяемые UI-компоненты (design/shared)

### Badge

* status / stage
* danger / neutral / accent

### ProgressBar

* mini для таблиц
* full для detail view

### Error Callout

* code + human text
* retry hint

**Итог:**
 Эти паттерны — “строительные блоки” UI.
 Новые экраны должны собираться из них, а не из уникальных решений.



Part 34.
 Appendix D — Модель данных (ERD текстом)

 Полный список таблиц MVP, их ключевые поля и связи.
 Это “карта данных” Autocontent.


Appendix / L2

### Принцип модели данных

**Cards — центр системы.**
 Все остальные сущности либо “принадлежат карточке” (photos/publish\_jobs),
 либо “обслуживают конвейер” (queue\_jobs/dlq/audit).


### Таблицы и поля

### 1) users

 id (PK), email (UQ), name, password\_hash, is\_active, created\_at, updated\_at

Назначаются роли через users\_roles.

### 2) roles

 id (PK), code (UQ), title, permissions\_json, created\_at


### 3) users\_roles

 user\_id (FK users.id), role\_id (FK roles.id), assigned\_at

Связь M:N пользователей и ролей.

### 4) cards

 id (PK),
 source, source\_id (UQ with source),
 status,
 vehicle\_json, price\_json, location\_json, text\_json,
 stage\_progress\_json,
 last\_error\_code, last\_error\_message,
 created\_at, updated\_at


 Единственная “истина” о карточке + её текущем статусе в pipeline.


### 5) parser\_payloads

 id (PK), card\_id (FK cards.id), schema\_version,
 raw\_json, normalized\_json, received\_at

Храним трассировку входящих данных.

### 6) photos

 id (PK), card\_id (FK cards.id),
 raw\_url, masked\_key, sort\_order,
 status, last\_error\_code, last\_error\_message,
 created\_at, updated\_at


### 7) exports

 id (PK), status, file\_key,
 card\_ids\_json,
 created\_by (FK users.id),
 created\_at, updated\_at


### 8) publish\_jobs

 id (PK), card\_id (FK cards.id),
 status, attempts,
 dolphin\_profile\_id, avito\_item\_id,
 last\_error\_code, last\_error\_message,
 created\_at, updated\_at


### 9) queue\_jobs

 id (PK), queue\_type,
 entity\_ref\_type, entity\_ref\_id,
 payload\_json,
 status (queued|processing|retrying|done|dead),
 attempts, next\_retry\_at,
 idempotency\_key (UQ),
 created\_at, updated\_at

Универсальная очередь для всех стадий.

### 10) dlq\_jobs

 id (PK), origin\_queue\_type,
 entity\_ref\_type, entity\_ref\_id,
 payload\_json,
 fatal\_reason, last\_error\_code, last\_error\_message,
 attempts, created\_at


### 11) audit\_logs

 id (PK), actor\_user\_id (FK users.id),
 action, entity\_ref\_type, entity\_ref\_id,
 before\_json, after\_json,
 correlation\_id, created\_at

Факт любых системных/пользовательских изменений.

### 12) system\_logs (опционально)

 id (PK), level, service, module, message,
 details\_json, correlation\_id, created\_at


### Связи (ERD словами)

 users 1—N audit\_logs
 users M—N roles (через users\_roles)

 cards 1—N parser\_payloads
 cards 1—N photos
 cards 1—N publish\_jobs

 exports N—M cards (через exports.card\_ids\_json в MVP,
 затем можно нормализовать в exports\_cards)

 queue\_jobs → entity\_ref (cards/photos/exports/publish\_jobs)
 dlq\_jobs → entity\_ref (cards/photos/exports/publish\_jobs)


**Итог:**
 Модель данных MVP зафиксирована. Cards — центр,
 jobs/lq/audit — обслуживают конвейер и наблюдаемость.



Part 35.
 Appendix E — Таблица State Machine

 “Законная” карта статусов карточки.
 UI показывает только допустимые действия, backend режет всё недопустимое.


Appendix / L2

### Статусы и переходы (MVP)

| Стадия | Статус | Кто ставит | Допустимые next | Кнопка в UI |
| --- | --- | --- | --- | --- |
| Cards | **draft** | Parser / Operator | photos\_queued, blocked | Start Photos |
| Photos | **photos\_queued** | Operator / System | photos\_processing | — |
| Photos | **photos\_processing** | PhotoWorker | photos\_ready, photos\_failed | — |
| Photos | **photos\_failed** | PhotoWorker | photos\_queued, blocked | Retry Photos |
| Photos | **photos\_ready** | PhotoWorker | ready\_for\_export | Mark Ready for Export |
| Export | **ready\_for\_export** | Operator | export\_queued | Create Export |
| Export | **export\_queued** | System | export\_processing | — |
| Export | **export\_processing** | ExportWorker | ready\_for\_publish, export\_failed | — |
| Export | **export\_failed** | ExportWorker | export\_queued, blocked | Retry Export |
| Publish | **ready\_for\_publish** | ExportWorker / Operator | publish\_queued | Start Publish |
| Publish | **publish\_queued** | System | publish\_processing | — |
| Publish | **publish\_processing** | PublishWorker | published, publish\_failed | — |
| Publish | **publish\_failed** | PublishWorker | publish\_queued, blocked | Retry Publish |
| Publish | **published** | PublishWorker | — | — |
| System | **blocked** | Admin | draft (manual reset) | Unblock (admin) |

**Итог:**
 State Machine — замороженный “закон” карточки.
 UI и backend обязаны ему соответствовать.



Part 36.
 Appendix F — Примеры конфигов и feature flags

 Наглядные примеры: endpoints, роли, флаги, лимиты.
 В репе это лежит в backend/src/Config.


Appendix / L2

### Config/endpoints.php (пример)

 return [
 "parser" => [
 "base\_url" => env("PARSER\_ENDPOINT"),
 "timeout\_ms" => 8000,
 ],
 "photo\_api" => [
 "base\_url" => env("PHOTO\_API\_ENDPOINT"),
 "timeout\_ms" => 15000,
 ],
 "s3" => [
 "endpoint" => env("S3\_ENDPOINT"),
 "bucket\_masked" => "masked-photos",
 "bucket\_exports" => "exports",
 ],
 "dolphin" => [
 "base\_url" => env("DOLPHIN\_ENDPOINT"),
 "token" => env("DOLPHIN\_TOKEN"),
 ],
 "avito" => [
 "accounts" => explode(",", env("AVITO\_ACCOUNT\_IDS")),
 ],
 ];


### Config/roles.php (пример)

 return [
 "operator" => [
 "cards.read", "cards.edit",
 "photos.start", "photos.retry",
 "export.create", "export.download",
 "publish.start", "publish.retry",
 ],
 "admin" => [
 "all.operator",
 "queues.view", "queues.pause", "queues.resume",
 "dlq.view", "dlq.retry", "dlq.bulk\_retry",
 "logs.read", "integrations.view",
 ],
 "owner" => [
 "all.admin",
 "integrations.edit",
 "users.manage", "roles.manage",
 "system.feature\_flags.edit",
 ],
 ];


### Config/feature\_flags.php (пример)

 return [
 "robot\_dry\_run" => env\_bool("FF\_ROBOT\_DRY\_RUN", true),
 "photos\_parallelism" => env\_int("FF\_PHOTOS\_PARALLELISM", 4),
 "publish\_rate\_limit\_per\_hour" => env\_int("FF\_PUBLISH\_RPH", 20),
 "contracts\_strict\_mode" => env\_bool("FF\_CONTRACTS\_STRICT", true),
 ];


### Как флаги влияют на поведение

* **robot\_dry\_run**: робот проходит сценарий, но не подтверждает публикацию.
* **photos\_parallelism**: сколько фото одновременно гоняем в PhotoWorker.
* **publish\_rate\_limit\_per\_hour**: жёсткий лимит публикаций на аккаунт.
* **contracts\_strict\_mode**: неизвестная schema\_version сразу в DLQ.

**Итог:**
 Конфиги и feature flags — официальный рычаг управления поведением системы
 без переписывания кода.



Part 37.
 Appendix G — Каталог ошибок и retry-политика

 Единые правила обработки ошибок во всех стадиях pipeline:
 классификация, коды, условия retry и DLQ.


Appendix / L2

### Принцип ошибок

 Ошибка — это не “провал”, а **сигнал стадии pipeline**.
 Мы обязаны либо:
 **исправиться retry**, либо
 **остановиться в DLQ** с понятной причиной.


### Классификация ошибок

| Класс | Описание | Примеры | Действие |
| --- | --- | --- | --- |
| **Transient** | Временная, сама проходит | timeouts, 502/503, network fail | Retry |
| **RateLimit** | Лимиты внешнего API | 429, anti-bot soft ban | Retry с backoff |
| **Validation** | Payload невалиден | schema mismatch, missing field | DLQ |
| **Business** | Доменное правило нарушено | нет фото, нет цены, forbidden status transition | UI fix / DLQ |
| **Fatal** | Не исправляется автоматически | unsupported schema\_version, broken mapping | DLQ |

### Единый формат кода ошибки

...`Примеры:
 PARSER.INTAKE.VALIDATION.MISSING_FIELD
 PHOTOS.MASK.TRANSIENT.TIMEOUT
 PUBLISH.ROBOT.RATELIMIT.HTTP_429
 EXPORT.GENERATE.BUSINESS.NO_CARDS`

### RetryPolicy (MVP)

| Попытка | Задержка | Примечание |
| --- | --- | --- |
| 1 | 1 мин | быстрое восстановление |
| 2 | 5 мин | типичный transient |
| 3 | 15 мин | на случай перегруза |
| 4 | 1 час | под лимиты/окна сервисов |
| 5 | 6 часов | последний автоматический шанс |

* После 5-й попытки → DLQ.
* Для RateLimit ошибки backoff умножается x2.
* Validation/Fatal не ретраим.

### Как UI показывает ошибки

* В CardsList — красный badge с кодом класса.
* В CardDetails — Error Callout (code + human text + hint).
* Кнопки Retry доступны только если статус *\_failed.

**Итог:**
 Ошибки типизированы и кодируются единообразно.
 Retry только для transient/ratelimit; validation/fatal → DLQ.



Part 38.
 Appendix H — Инструкции для Codex/агента

 Короткий, но жёсткий набор правил для AI-агента,
 чтобы он генерировал код строго в рамках замороженной архитектуры.


Appendix / L2

### Роль Codex в проекте

 Codex — это “инженер-исполнитель”. Он не придумывает архитектуру,
 а **реализует утверждённое**.
 Любые отклонения от структуры = ошибка.


### Правила чтения репозитория

1. Сначала прочитай `README.md` и `AGENT.md`.
2. Пойми структуру: backend / external / frontend / infra / docs / tests.
3. Найди домен, к которому относится задача.
4. Проверь StateMachine и контракты до написания кода.

### Правила генерации кода (строгие)

### Backend

* Доменный код только в `backend/src/Modules/<Domain>`.
* Интеграции только в `backend/src/Adapters`.
* Фоновые задачи только через `Queues` + `Workers`.
* Любая смена статуса — через `StateMachine`.
* Ошибки обязаны иметь код формата Appendix G.

### Frontend

* UI собирается из primitives в `design/`.
* Сетевые вызовы только через `shared/api`.
* Feature-логика в `features/<domain>`.
* Роуты и страницы только в `apps/operator` или `apps/admin`.
* События WS должны маппиться в store.

### Что агенту запрещено делать

* Переименовывать верхние папки проекта.
* Класть доменную логику в Adapters/Utils.
* Прямые fetch/axios из компонентов UI мимо shared/api.
* Добавлять статусы без обновления StateMachine + docs + tests.
* Менять контракты без bump версии и fixtures.
* Удалять audit/логирование “ради чистоты кода”.

### Обязательный порядок работы агента над задачей

1. Определи домен задачи.
2. Проверь существующие контракты и StateMachine.
3. Сгенерируй/обнови схему (если нужен новый формат).
4. Добавь код в правильные папки.
5. Добавь минимум 1 unit и 1 integration тест.
6. Обнови docs (OpenAPI/WS/architecture если требуется).

**Итог:**
 Codex работает как исполнитель строго по замороженной структуре:
 домены → Modules/Features, интеграции → Adapters+Contracts, статусы → StateMachine.



Part 39.
 Appendix I — Quick Start для новых разработчиков

 Максимально короткий вход: как поднять проект локально,
 что проверить и как начать фичу, не сломав архитектуру.


Appendix / L2

### 1) Поднять проект локально

 git clone <repo>
 cp .env.example .env
 docker compose up -d
 # backend миграции/seed:
 docker compose exec backend php src/DB/Migrations/run.php
 docker compose exec backend php src/DB/Seed/run.php
 # frontend:
 docker compose exec frontend npm i
 docker compose exec frontend npm run dev


### 2) Проверка что всё ок

1. `GET /admin/health` → ok.
2. WS подключение работает (видно ping/hello event).
3. Залей fixtures Parser → появились draft cards.
4. Запусти Photos/Export/Publish на демо карточке.

### 3) Где что смотреть

* Бизнес-логика: `backend/src/Modules`.
* Интеграции: `backend/src/Adapters` + `external/*`.
* UI доменов: `frontend/src/features`.
* Страницы: `frontend/src/apps/operator|admin`.
* Документация: `docs/` (openapi, ws-events, C4).
* Тесты: `tests/`.

### 4) Как начать новую фичу

1. Создай ветку `feature/<domain>-<short>`.
2. Определи, нужен ли новый контракт или статус.
3. Добавь/обнови Modules + features.
4. Проверь DoD (Part 23) и добавь тесты.
5. Открой PR с описанием сценария проверки.

### 5) Если не уверен куда класть код

 "Это домен?" → Modules/Features
 "Это интеграция?" → Adapters + external/contracts
 "Это UI-инфра?" → shared/
 "Это деплой/сервис?" → infra/
 "Это документ/схема?" → docs/ + external/contracts


**Документ закрыт.**
 Part 1–39 фиксируют полный каркас Autocontent,
 его архитектуру, структуру репозитория и правила развития.



Part 40.
 Appendix J — Топология деплоя и окружения

 Наглядно фиксируем, какие сервисы где живут физически,
 как они связаны, и чем отличаются dev/stage/prod окружения.


Appendix / L2

### Принцип топологии

 Autocontent разворачивается как набор независимых сервисов,
 объединённых сетью и общими секретами.
 Frontend и Backend всегда “вместе”, а внешние/on-prem сервисы — отдельным слоем.


### Окружения

| Окружение | Цель | Состав | Секреты |
| --- | --- | --- | --- |
| **dev** | локальная разработка | docker-compose, fixtures вместо реальных данных | .env локально |
| **stage** | предпрод, интеграции | реальные Parser/Photo/MinIO/Dolphin, dry-run publish | vault/secrets |
| **prod** | боевой режим | полный pipeline, rate-limits, мониторинг, бэкапы | vault/secrets + ротация |

### Физическая раскладка сервисов

### Слой ядра (внутри проекта)

* **backend** (php-fpm + nginx)
* **workers** (php cli consumers)
* **ws** (внутри backend)
* **frontend** (react build + nginx)
* **db** (postgres/mysql)
* **redis/broker** (если в проде)

### Слой внешних/on-prem сервисов

* **parser** (auto-parser.ru API)
* **photo-api** (наш маскер номеров)
* **storage** (MinIO/S3 compatible)
* **dolphin** (антибраузер API)
* **avito** (площадка, через робота)

 [User Browser]
 |
 v
 (Frontend Nginx) ---REST/WS---> (Backend API + WS)
 |
 v
 (DB)
 |
 v
 (Queues/Broker)
 |
 v
 (Workers Pool)
 |
 ----------------------------------------------------------------
 | | | | |
 v v v v v
 Parser API Photo API MinIO/S3 Dolphin API Avito
 (in) (mask) (store) (profiles) (target)


### Сетевые границы

* Frontend — публичный вход (https).
* Backend — доступен только через ingress/nginx.
* Workers — без публичных портов, в приватной сети.
* Photo API + MinIO — приватный сегмент (on-prem).
* Dolphin/Parser/Avito — внешняя сеть, доступ только из backend/workers.

### Compose vs Kubernetes

### docker-compose (dev/stage)

* Быстрый локальный старт.
* Все сервисы в одном файле.
* Легко включать fixtures.

### k8s (prod при росте)

* Горизонтальное масштабирование воркеров.
* Rolling updates без даунтайма.
* Удобные secrets/configmaps.

### Рычаги масштабирования (где увеличиваем ресурсы)

* **workers replicas** → ускоряем конкретную стадию.
* **photo-api replicas/узел** → ускоряем маскировку фото.
* **db resources + read replica** → ускоряем списки/фильтры.
* **minio nodes/disks** → повышаем throughput хранения.

**Итог:**
 Топология зафиксирована: ядро (frontend/backend/workers/db) живёт вместе,
 внешние и on-prem сервисы — отдельный слой.
 Dev/Stage/Prod отличаются только источниками данных, секретами и ограничениями publish.



Part 41.
 Appendix K — Метрики, SLA и дашборды

 Фиксируем “что считается нормой” для Autocontent:
 основные метрики pipeline, SLA по стадиям, алерты и виджеты Admin UI.


Appendix / L2

### Принцип наблюдаемости

 Наблюдаемость нужна, чтобы **видеть деградацию раньше бизнеса**.
 Мы меряем не “всё подряд”, а то, что говорит:
 pipeline жив? где узкое место? что сломалось? кто должен реагировать?


### Категории метрик

### Throughput

* cards\_ingested\_per\_hour
* photos\_processed\_per\_hour
* exports\_generated\_per\_hour
* published\_per\_hour

### Latency

* time\_draft\_to\_photos\_ready
* time\_photos\_ready\_to\_exported
* time\_ready\_for\_publish\_to\_published
* total\_time\_draft\_to\_published

### Reliability

* retry\_rate\_by\_stage
* dlq\_rate\_by\_stage
* fatal\_errors\_topN
* success\_rate\_by\_stage

### SLA по стадиям (MVP ориентиры)

| Стадия | SLA (95% карточек) | Что влияет | Если хуже — куда смотреть |
| --- | --- | --- | --- |
| **Parser → draft** | < 2 мин | intake + DB write | Adapter Parser / DB |
| **Photos** | < 30 мин | CPU/GPU + Photo API + S3 | photos queue depth, photo-api health |
| **Export** | < 10 мин | генератор + S3 upload | export workers, DB locks |
| **Publish** | < 2 часа | лимиты Dolphin/Avito | publish queue, rate-limit, anti-bot |
| **Draft → Published total** | < 4 часа | сумма стадий + ручные шаги | по stage latency breakdown |

 Точные SLA фиксируются после 1–2 недель прод-замеров.


### Алерты (что должно “звонить”)

### Queue alerts

* photos\_queue\_depth > X (например 500)
* export\_queue\_depth > Y
* publish\_queue\_depth > Z
* job\_age\_p95 > SLA*2

### Error alerts

* dlq\_rate\_by\_stage spikes
* fatal\_errors\_topN change
* success\_rate\_by\_stage < threshold
* health.updated = fail

* Адресаты: Owner/Admin on-call.
* В алерте обязательно: stage, error\_code, correlation\_id, ссылка на Admin UI.

### Admin Dashboard (виджеты)

### Pipeline Overview

* Cards by status (stacked)
* Stage latency p50/p95
* Success rate by stage

### Queues Panel

* Depth per queue
* In-flight jobs
* Retrying jobs
* Pause/Resume

### Integrations Health

* Parser / Photo / S3 / Dolphin
* Latency sparkline
* Last error

### DLQ Panel

* DLQ count
* Top fatal reasons
* Bulk retry

### Publish Monitor

* Published/hour
* Anti-bot blocks
* Accounts/profiles load

### Audit / Ops

* Last admin actions
* Operator activity
* Suspicious spikes

### Источники метрик

* DB агрегации по cards/status + jobs.
* Workers emit counters/timers (если Prometheus).
* Health checks интеграций.
* WS используется только для UI, не как источник метрик.

**Итог:**
 Метрики и SLA зафиксированы как ориентиры MVP.
 Admin UI показывает throughput/latency/reliability, алерты строятся на очередях,
 DLQ и health интеграций.



Part 42.
 Appendix L — Релизы, миграции и rollback

 Правила выпуска новых версий Autocontent:
 как катим код, как мигрируем БД и как быстро откатываемся при проблемах.


Appendix / L2

### Принцип релизов

 Релиз = безопасное изменение pipeline без остановки бизнеса.
 Поэтому: **сначала совместимость**, потом включение,
 и всегда есть план отката.


### Типы релизов

| Тип | Что меняем | Риск | Как катим |
| --- | --- | --- | --- |
| **Patch** | фиксы, мелкие улучшения | низкий | rolling update |
| **Minor** | новые optional поля/фичи | средний | rolling + feature flags |
| **Major** | breaking API/StateMachine/contracts | высокий | blue/green + параллельные версии |

### Миграции БД

* Миграции лежат в `backend/src/DB/Migrations`.
* Каждая миграция — маленькая и обратимая.
* Нельзя делать длительные lock-операции в prod-пике.
* Любая новая колонка сначала nullable, потом заполняем, потом делаем not null.

Правильный порядок для новых полей

 1) add column nullable
 2) deploy code that writes/reads it
 3) backfill existing rows via worker/cron
 4) add constraint not null (если нужно)


### Стратегия выката

1. Катим backend/API + workers (совместимые).
2. Катим frontend (он должен жить и со старым API).
3. Если есть новый контракт/статус:
 **поддержка в коде → включение через флаг → снятие старой версии**.
4. Смотрим метрики/очереди/DLQ 30–60 минут.
5. Если норм — фиксируем новую версию в release notes.

### Rollback — когда и как

### Триггеры отката

* DLQ\_rate spike > x3 за 10 мин.
* Publish success\_rate < threshold.
* photos\_queue\_depth растёт без снижения.
* health.updated = fail для критичного сервиса.

### Процедура

1. Отключить фичу флагом (если возможно).
2. Откатить backend/workers на предыдущий image tag.
3. Откатить frontend (если ломает UX).
4. Если миграция breaking — применить down-migration или hotfix.
5. Проверить queues/DLQ и прогнать 1 happy-path карточку.

 Если откат связан с контрактом → возвращаем источник на старую schema\_version
 только после того, как код снова её поддерживает.


### Release notes (что пишем обязательно)

* Номер версии + дата.
* Какие домены трогали.
* Изменения StateMachine/Contracts (если есть).
* Новые флаги + их дефолты.
* Риски и план отката.

**Итог:**
 Релизы безопасные: совместимость сначала, включение потом, rollback всегда готов.
 Миграции маленькие, обратимые и staged.



Part 43.
 Appendix M — Phase 2: план развития и точки расширения

 Финальная карта того, как Autocontent расширяется после MVP:
 какие фичи логичны следующими, где их реализовывать в структуре,
 и какие “гибкие стыки” уже предусмотрены архитектурой.


Appendix / L2 — roadmap

### Принцип Phase 2

 Phase 2 не меняет фундамент.
 Мы усиливаем надёжность, удобство операторов и глубину автоматизации
 **через заранее предусмотренные точки расширения**:
 Modules/Features, Adapters/Contracts, Workers/Queues, StateMachine, Feature flags.


### Точки расширения (куда добавлять новое)

| Что расширяем | Где в backend | Где во frontend | Нужно ли менять контракты |
| --- | --- | --- | --- |
| Новый домен (напр. Moderation) | `Modules/Moderation` + (Workers при нужде) | `features/moderation` + страницы apps | да, если новый вход/выход |
| Новая интеграция (напр. 2-я площадка) | `Adapters/NewPlatformAdapter` | только новый UI flow, если надо | да, в `external/new-platform` |
| Новая стадия pipeline | StateMachine + Modules + Workers + Queues | новый статусный UI + actions | возможно |
| Улучшение UX/операторских инструментов | как правило без изменений | features/* + design/shared | нет |

### Backlog Phase 2 (приоритеты)

### P1 — надёжность и контроль

1. **Guaranteed events** (WS cursor / event log).
2. **Отложенные публикации** (schedule publish).
3. **Авто-ремедиация DLQ** (policy-based retry).
4. **Read replicas DB** для листингов.

### P1 — удобство операторов

1. **Массовые правки** по шаблонам.
2. **Сравнение версий карточки** (diff view).
3. **Умные подсказки** “почему не проходит”.
4. **Визуальные цепочки ошибок** по correlation\_id.

### P2 — глубже автоматизация

1. **Auto-moderation** (правила качества карточки).
2. **Auto-export** по расписанию/триггерам.
3. **Republish** (обновление цены/текста).
4. **Авто-распределение аккаунтов** Avito по нагрузке.

### P3 — мультиплатформенность

1. Новые площадки: Drom, Youla и т.п.
2. Единый “Publish Hub” адаптеров.
3. Сводная витрина статусов по площадкам.

### Какие новые модули вероятны в Phase 2

* **Modules/Moderation** — правила качества, авто-блокировки.
* **Modules/Scheduler** — планирование публикаций/экспортов.
* **Modules/Republish** — обновление активных объявлений.
* **Modules/Analytics** — витрина KPI, SLA, прогнозы очередей.
* **Adapters/PlatformXAdapter** — подключение новых площадок.

### Как будет расширяться State Machine

* Добавление новых веток, но без ломания существующих стадий.
* Новые статусы всегда с префиксом стадии (`moderation_*`, `republish_*`).
* Старые статусы остаются валидными минимум 1 major релиз.

### Ограничения (guardrails) Phase 2

* Нельзя ломать contracts-first.
* Нельзя обходить Adapters при интеграциях.
* Нельзя добавлять “ручные” статусы без StateMachine.
* Любая автоматизация должна быть обратима флагом.

**Финальный итог документа:**
 Phase 2 спланирован как расширение через существующие точки роста.
 Фундамент (структура репы, pipeline, contracts, state machine, UI слои)
 остаётся замороженным.



Part 44.
 Appendix N — Глоссарий терминов Autocontent

 Словарь ключевых терминов проекта.
 Это фиксирует “единый язык” для команды, чтобы не было разночтений.


Appendix / L1-L2

### Базовые сущности

* **Card (Карточка)** — центральная сущность, будущая публикация на Avito. Содержит данные авто, фото, тексты, статусы конвейера.
* **Parser payload** — JSON от auto-parser.ru/Auto.ru, входящий в систему.
* **Photo** — единица изображения в карточке (raw → masked → stored).
* **Export** — пакет карточек в формате выгрузки (XLSX/JSON).
* **Publish job** — задача публикации одной карточки.

### Pipeline / Технические термины

* **Pipeline (Конвейер)** — последовательность стадий: Parser → Photos → Export → Publish.
* **Stage (Стадия)** — этап pipeline, имеющий свой статус и очередь.
* **Status** — состояние карточки в StateMachine (draft, photos\_ready, published и т.п.).
* **State Machine** — “закон” допустимых статусов и переходов карточки.
* **Queue job** — запись задания в очереди (универсально для всех стадий).
* **Worker** — фоновый consumer конкретной очереди.
* **Retry** — повторное выполнение job по policy.
* **DLQ (Dead Letter Queue)** — хранилище фатальных job, которые не исправляются автоматом.

### Интеграции / внешние сервисы

* **Adapter** — фасад для любой интеграции (ParserAdapter, PhotoApiAdapter и т.д.).
* **Contract** — JSON schema формата входа/выхода сервиса.
* **Photo API** — on-prem сервис маскировки номеров.
* **S3/Storage** — MinIO/S3-совместимое хранилище фото и экспортов.
* **Dolphin Anty** — антибраузер, выдаёт профили/сессии роботу.
* **Robot Service** — внутренний модуль, который кликает/публикует через Dolphin → Avito.

### UI / роли

* **Operator UI** — рабочий фронт для операторов (карточки, фото, экспорт, публикация).
* **Admin UI** — контур админа (очереди, DLQ, логи, здоровье сервисов).
* **RBAC** — модель прав доступа по ролям.
* **Feature flag** — переключатель поведения системы без релиза (dry-run, лимиты и т.п.).

**Итог:**
 Этот глоссарий — официальный язык проекта. Любые новые термины добавляются сюда.



Part 45.
 Appendix O — Регламенты ролей и ответственности

 Фиксируем, кто и за что отвечает в Autocontent,
 чтобы pipeline жил в проде без “серых зон”.


Appendix / L2

### Роль Operator

### Задачи

* Проверить draft карточки, исправить поля.
* Запустить Photos и контролировать прогресс.
* Создать Export, скачать пакет.
* Запустить Publish и смотреть статусы Avito.

### Когда вмешиваться

* Статус *\_failed.
* В карточке есть ошибки Business.
* Не хватает фото/текста/цены.

### SLA Operator

* Draft → start photos: не более X часов после прихода.
* Failed статусы: разбор в течение Y часов.

### Роль Admin

### Задачи

* Следить за глубиной очередей.
* Разбирать DLQ (retry/фикс/эскалация).
* Смотреть health интеграций и их latency.
* Управлять ролями и фичефлагами.

### Когда вмешиваться

* Queue depth растёт без снижения.
* DLQ spikes или новые fatal коды.
* Health сервиса = fail.

### SLA Admin / On-call

* Критичный алерт очередей/health: реакция ≤ 15 мин.
* DLQ фаталы: triage ≤ 2 часа.

### Роль Owner (тех/продукт)

* Утверждает изменения StateMachine.
* Подписывает major изменения контрактов.
* Определяет SLA и лимиты публикации.
* Решает, что уходит в Phase 2.

### Критичные сценарии эскалации

* Publish остановился из-за антибота → Admin → Owner → решение по аккаунтам/профилям.
* Parser сменил формат → Admin/Owner → bump schema → Adapter hotfix.
* Photo API не отвечает → Admin → fallback очередь/пауза → ремонт сервиса.

**Итог:**
 Роли и ответственности зафиксированы: Operator ведёт карточки,
 Admin держит живым pipeline и интеграции, Owner утверждает изменения фундамента.



 Завершающий блок — фиксация документа Autocontent

 Этот документ — единый источник истины по архитектуре, структуре репозитория
 и правилам развития Autocontent.


### Статус

 Архитектура и структура проекта **утверждены и заморожены**.
 Любые изменения фундаментальных частей возможны только через
 процедуру major-изменений (contracts-first → state machine → tests → release).


### Что считается “фундаментом” (не меняем без major)

* Структура репозитория: `backend / external / frontend / infra / docs / tests`.
* Pipeline стадий и их границы: Parser → Photos → Export → Publish.
* State Machine статусов карточки.
* Contracts-first и версионирование схем.
* Adapters как единственный слой интеграций.
* Очереди/воркеры + DLQ + retry-policy.
* Слоистая архитектура фронта (design/shared/features/apps).

### Что можно расширять свободно (в рамках правил)

* Новые домены: добавляем как `Modules/<Domain>` и `features/<domain>`.
* Новые интеграции: добавляем как `Adapters/*` + `external/*/contracts`.
* Новые экраны UI: собираем из design/shared и доменных features.
* Оптимизации и UX-улучшения без ломания контрактов/статусов.
* Feature flags для безопасного включения изменений.

### Как работать с этим документом

1.
 Перед новой фичей: сверяемся с **State Machine** и **Contracts**.
2.
 Любой новый формат данных: сначала схема + fixtures, потом код.
3.
 Любой новый статус/стадия: обновляем StateMachine + docs + tests.
4.
 Codex/агент работает **строго по Appendix H**.

### Версия документа

 Document: Autocontent Architecture & Repo Blueprint
 Version: 1.0 (Frozen)
 Date: 2025-12-08
 Owners: Product/Tech owner + Backend lead + Frontend lead
 Change policy: Minor additions allowed. Major changes require new version.


### Следующие шаги команды

1. Идём в реализацию по фазам A–F (см. Part 21–22).
2. Сначала backend happy-path, затем UI и стабильность.
3. После MVP — Phase 2 только через точки расширения.

**Документ завершён.**
 С этого момента он является опорой для разработки и работы Codex.
 Любые отклонения от утверждённого фундамента считаются архитектурным изменением
 и требуют отдельного согласования.
