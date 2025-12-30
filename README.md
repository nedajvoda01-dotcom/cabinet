# Cabinet Platform

**Тонкая платформа для управления возможностями (capabilities)**

## Архитектура

Cabinet — это минималистичная платформа-маршрутизатор, которая:
- Принимает запросы от UI клиентов
- Маршрутизирует их к адаптерам на основе capabilities
- Применяет политики безопасности и ограничения
- Фильтрует результаты через ResultGate

### Принципы

1. **Тонкое ядро** — Platform не содержит бизнес-логики
2. **Декларативная конфигурация** — всё управляется через registry/*.yaml
3. **Расширяемость без изменений кода** — новые адаптеры и UI добавляются через конфигурацию
4. **Разделение ответственности** — Platform (trusted) vs Adapters/UI (untrusted)

## Компоненты

### Platform (trusted)
Тонкое ядро системы:
- `Router` — маршрутизация capability → adapter
- `Policy` — проверка доступа и scopes
- `Limits` — rate limiting, timeouts, размеры запросов
- `ResultGate` — фильтрация результатов
- `Storage` — минимальное хранилище состояния

### Registry (data)
Источник истины — декларативная конфигурация:
- `adapters.yaml` — список адаптеров
- `capabilities.yaml` — привязка capabilities к адаптерам
- `ui.yaml` — разрешения для UI
- `policy.yaml` — роли и ограничения

### Adapters (untrusted)
Вся бизнес-логика системы:
- `car-storage` — управление данными автомобилей
- `pricing` — расчёты цен
- `automation` — автоматизация процессов

### UI (untrusted)
Единый клиентский интерфейс с capability-based доступом:
- `ui-unified` — unified UI (адаптируется под роль пользователя)
  - **Public Profile**: каталог и просмотр (4 capabilities)
  - **Admin Profile**: полный доступ (17+ capabilities)

> **Примечание**: Старые UI (`ui/admin`, `ui/public`) устарели и будут удалены.

## Быстрый старт

```bash
# Копируем пример конфигурации
cp .env.example .env

# Запускаем локально через Docker Compose
docker-compose up

# Или используем скрипт
./scripts/run-local.sh
```

Platform будет доступен на `http://localhost:8080`

### Unified UI

Единый интерфейс с динамической адаптацией под права пользователя:

- **Guest/Public**: `http://localhost:8080/ui/` (4 capabilities, только каталог)
- **Admin**: Войти через UI с ролью "Admin" (17+ capabilities, полный доступ)

См. [ui-unified/README.md](ui-unified/README.md) для подробностей.

## Тестирование

```bash
# Запуск smoke тестов
cd tests
# Используйте HTTP клиент для выполнения smoke.http
```

## Добавление нового адаптера

1. Создайте директорию в `adapters/`
2. Добавьте `invoke.php` и `capabilities.yaml`
3. Зарегистрируйте в `registry/adapters.yaml`
4. Привяжите capabilities в `registry/capabilities.yaml`
5. Перезапустите platform

**Код платформы менять не нужно!**

## Добавление нового UI

Unified UI адаптируется под роль автоматически. Для добавления новых возможностей:

1. Добавьте capability в `registry/ui.yaml` для нужного профиля
2. Создайте страницу в `ui-unified/src/pages/`
3. Зарегистрируйте route в `ui-unified/src/app.js`
4. Добавьте проверку capability через guards

См. [ui-unified/README.md](ui-unified/README.md) для подробной инструкции.

**Код платформы менять не нужно!**

## Конвертация Registry

Если вы изменили YAML файлы в `registry/`, конвертируйте их в JSON:

```bash
python3 scripts/convert-registry.py
```

Это необходимо для работы PHP platform (YAML extension не установлен).

## Лицензия

MIT
