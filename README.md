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
Клиентские интерфейсы:
- `admin` — административная панель
- `public` — публичный интерфейс

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

1. Создайте директорию в `ui/`
2. Зарегистрируйте в `registry/ui.yaml` с нужными capabilities
3. Настройте в docker-compose.yml

**Код платформы менять не нужно!**

## Лицензия

MIT
