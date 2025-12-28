Shared Contracts - Единые контракты
🎯 Назначение
Shared Contracts — это единый источник истины для всех типов данных и контрактов между компонентами системы Cabinet. Мы устраняем расхождения между фронтендом и бэкендом, централизуя определение типов, схем и тестовых данных.

📁 Структура каталога
text
shared/contracts/
├── README.md                      # Этот файл
├── primitives/                    # Фундаментальные типы системы
│   ├── Status.md                  # Статусы операций и задач
│   ├── ErrorKind.md               # Классификация ошибок
│   ├── TraceContext.md            # Контекст трассировки запросов
│   ├── CapabilitySet.md           # Набор возможностей пользователя
│   └── AssetRef.md                # Ссылки на ресурсы (задачи, файлы и т.д.)
├── vectors/                       # Тестовые данные для проверки реализации
│   ├── nonce-vectors.json         # Примеры корректных/некорректных nonce
│   ├── signature-vectors.json     # Тестовые подписи для верификации
│   ├── encryption-vectors.json    # Тестовые данные шифрования
│   └── canonicalization-vectors.json # Примеры канонизации для подписей
└── implementations/               # Сгенерированный код
    ├── php/                       # PHP реализации
    │   ├── Status.php
    │   ├── Error.php
    │   ├── ErrorKind.php
    │   ├── AssetRef.php
    │   ├── IntegrationHealth.php
    │   ├── CapabilitySet.php
    │   └── TraceContext.php
    └── typescript/                # TypeScript типы
        ├── status.ts
        ├── errorKind.ts
        ├── assetRef.ts
        ├── capabilitySet.ts
        └── traceContext.ts
🚫 Проблема, которую решаем
До внедрения Shared Contracts:

typescript
// app/frontend/src/shared/api/contracts/common.ts
interface Task {
  id: string;
  status: 'pending' | 'processing' | 'completed'; // ← одни значения
}

// app/backend/src/Application/Contracts/Status.php
class Status {
  const PENDING = 'pending';
  const IN_PROGRESS = 'in_progress'; // ← другие значения!
  const DONE = 'done';
}
Симптомы:

Расхождения в именовании статусов

Разные форматы ошибок

Несовместимые структуры данных

Сложность синхронизации изменений

✅ Решение
1. Единая точка определения
Все общие типы определяются единожды в markdown-файлах с чёткими спецификациями:

Пример primitives/Status.md:

markdown
# Status - Статусы операций

## Назначение
Универсальные статусы для всех операций в системе.

## Значения
- `pending` - Ожидает обработки
- `processing` - В процессе выполнения  
- `completed` - Успешно завершён
- `failed` - Завершился ошибкой
- `cancelled` - Отменён пользователем

## Правила
1. Все новые операции начинаются с `pending`
2. Переход `pending` → `processing` происходит автоматически
3. Из `completed` нельзя вернуться в другие статусы
4. `failed` может быть перезапущен (возврат в `pending`)
2. Автоматическая генерация кода
Workflow:

text
1. Разработчик изменяет Status.md
2. Запускает генератор: make generate-contracts
3. Генераторы создают:
   - implementations/php/Status.php
   - implementations/typescript/status.ts
4. Изменения коммитятся вместе
Команды:

bash
# Сгенерировать все контракты
make generate-contracts

# Сгенерировать только PHP
make generate-contracts-php

# Сгенерировать только TypeScript  
make generate-contracts-ts

# Проверить соответствие
make validate-contracts
3. Импорт в проекты
Backend (PHP):

php
// app/backend/composer.json
{
    "autoload": {
        "psr-4": {
            "App\\": "src/",
            "SharedContracts\\": "../shared/contracts/implementations/php/"
        }
    }
}

// Использование
use SharedContracts\Status;
use SharedContracts\ErrorKind;

class TaskService {
    public function create(): Task {
        return new Task(
            status: Status::PENDING,
            // ...
        );
    }
}
Frontend (TypeScript):

typescript
// app/frontend/package.json
{
  "dependencies": {
    "@shared/contracts": "file:../shared/contracts/implementations/typescript"
  }
}

// Использование
import { Status, ErrorKind } from '@shared/contracts';

interface Task {
  id: string;
  status: Status; // Автодополнение работает!
}

if (task.status === Status.PENDING) {
  // ...
}
🔬 Тестовые векторы (vectors/)
Назначение
Векторы содержат тестовые данные для проверки реализации криптографических и других чувствительных алгоритмов.

Пример vectors/signature-vectors.json:

json
{
  "version": "1.0",
  "algorithm": "ED25519",
  "vectors": [
    {
      "name": "simple_request",
      "description": "Простой GET запрос",
      "input": {
        "method": "GET",
        "path": "/api/tasks",
        "query": "limit=10&offset=0",
        "headers": {
          "X-Timestamp": "2024-01-15T10:30:00Z",
          "X-Nonce": "01HQTK1R2XQ5Q7QZJZQZQZQZQZ"
        },
        "body": null
      },
      "canonical_form": "GET\n/api/tasks\nlimit=10&offset=0\nx-nonce:01HQTK1R2XQ5Q7QZJZQZQZQZQZ\nx-timestamp:2024-01-15T10:30:00Z\n",
      "private_key": "2f3b...",
      "expected_signature": "3a7f...",
      "purpose": "Проверка канонизации и подписи простого запроса"
    }
  ]
}
Использование векторов
Backend тест:

php
class SignatureServiceTest extends TestCase
{
    public function test_vectors(): void
    {
        $vectors = json_decode(
            file_get_contents('../../shared/contracts/vectors/signature-vectors.json'),
            true
        );
        
        foreach ($vectors['vectors'] as $vector) {
            $signature = $this->service->sign(
                $vector['input'],
                $vector['private_key']
            );
            
            $this->assertEquals(
                $vector['expected_signature'],
                $signature,
                "Vector failed: {$vector['name']}"
            );
        }
    }
}
Frontend тест:

typescript
import signatureVectors from '@shared/contracts/vectors/signature-vectors.json';

describe('Signature canonicalizer', () => {
  signatureVectors.vectors.forEach((vector) => {
    it(`should pass ${vector.name}`, () => {
      const canonical = canonicalizeRequest(vector.input);
      expect(canonical).toBe(vector.canonical_form);
    });
  });
});
📋 Список примитивов
1. Status - Статусы операций
Используется для: задач, заданий pipeline, операций пользователя.

2. ErrorKind - Типы ошибок
text
validation      - Ошибка валидации входных данных
authentication  - Проблемы аутентификации
authorization   - Недостаточно прав
not_found       - Ресурс не найден
rate_limit      - Превышен лимит запросов
integration     - Ошибка внешнего сервиса
internal        - Внутренняя ошибка системы
3. TraceContext - Контекст трассировки
typescript
{
  traceId: string;      // ULID трассировки
  spanId: string;       // ULID текущего span
  parentSpanId?: string; // ULID родительского span
  userId?: string;      // ULID пользователя (если аутентифицирован)
  sessionId?: string;   // ID сессии
  clientId?: string;    // Идентификатор клиента (браузер, мобильное приложение)
}
4. CapabilitySet - Возможности пользователя
typescript
// ВМЕСТО РОЛЕЙ!
type CapabilitySet = {
  // Задачи
  can_create_task: boolean;
  can_view_all_tasks: boolean;
  can_edit_own_tasks: boolean;
  
  // Пользователи
  can_invite_users: boolean;
  can_view_user_hierarchy: boolean;
  
  // Администрирование
  can_view_system_metrics: boolean;
  can_manage_integrations: boolean;
  
  // Безопасность
  can_view_audit_logs: boolean;
  can_manage_api_keys: boolean;
};
5. AssetRef - Ссылка на ресурс
Универсальная ссылка на любой ресурс в системе:

typescript
{
  type: 'task' | 'photo' | 'document' | 'user';
  id: string;           // ULID ресурса
  encryptedContext?: string; // Зашифрованный контекст (E2E)
  signature?: string;   // Подпись ссылки (защита от подделки)
}
🔄 Рабочий процесс разработки
Сценарий: Добавление нового статуса
Шаг 1: Определение в primitives/

markdown
# В primitives/Status.md добавляем:
- `archived` - Архивирован, доступен только для чтения
Шаг 2: Генерация кода

bash
make generate-contracts
Шаг 3: Проверка сгенерированного кода

PHP:

php
// implementations/php/Status.php
class Status {
    const PENDING = 'pending';
    const PROCESSING = 'processing';
    const COMPLETED = 'completed';
    const FAILED = 'failed';
    const CANCELLED = 'cancelled';
    const ARCHIVED = 'archived'; // ← новый статус!
    
    public static function all(): array {
        return [
            self::PENDING,
            self::PROCESSING,
            self::COMPLETED,
            self::FAILED,
            self::CANCELLED,
            self::ARCHIVED, // ← автоматически добавлен
        ];
    }
}
TypeScript:

typescript
// implementations/typescript/status.ts
export type Status = 
  | 'pending'
  | 'processing' 
  | 'completed'
  | 'failed'
  | 'cancelled'
  | 'archived'; // ← новый статус!

export const StatusValues = {
  PENDING: 'pending' as Status,
  PROCESSING: 'processing' as Status,
  COMPLETED: 'completed' as Status,
  FAILED: 'failed' as Status,
  CANCELLED: 'cancelled' as Status,
  ARCHIVED: 'archived' as Status, // ← автоматически добавлен
};
Шаг 4: Использование в коде
Теперь оба слоя используют одинаковые значения.

🧪 Валидация и тестирование
Автоматические проверки CI
.github/workflows/validate-contracts.yml:

yaml
name: Validate Contracts
on: [push, pull_request]

jobs:
  validate:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      
      - name: Generate contracts
        run: make generate-contracts
        
      - name: Check for changes
        run: |
          if git diff --name-only | grep -q "implementations/"; then
            echo "❌ Сгенерированный код не соответствует исходникам!"
            echo "Запустите: make generate-contracts"
            git diff
            exit 1
          fi
          
      - name: Run parity tests
        run: |
          cd app/frontend
          npm run test:contracts
Ручные проверки
bash
# Проверить соответствие OpenAPI и контрактов
make validate-openapi-parity

# Проверить, что все импорты используют shared/contracts
make check-contract-imports

# Найти "забытые" локальные определения
make find-local-contracts
🚨 Антипаттерны и запреты
❌ НЕЛЬЗЯ
php
// app/backend/src/Application/Services/TaskService.php
class TaskService {
    // Не определять константы здесь!
    const STATUS_NEW = 'new'; // ❌
    
    public function create() {
        return ['status' => 'new']; // ❌ строковые литералы
    }
}
typescript
// app/frontend/src/entities/task/model.ts
interface Task {
  status: 'new' | 'in_work' | 'done'; // ❌ локальное определение
}
✅ НУЖНО
php
use SharedContracts\Status;

class TaskService {
    public function create() {
        return ['status' => Status::PENDING]; // ✅
    }
}
typescript
import { Status } from '@shared/contracts';

interface Task {
  status: Status; // ✅
}
📊 Мониторинг использования
Скрипт проверки: scripts/check-contract-usage.sh

bash
#!/bin/bash
# Находит все использования строковых литералов вместо контрактов

echo "🔍 Поиск строковых статусов..."
grep -r "'pending'\|'processing'\|'completed'" app/backend/src app/frontend/src --include="*.php" --include="*.ts" --include="*.tsx"

echo "🔍 Поиск локальных enum/const определений..."
grep -r "const.*=.*'pending'" app/backend/src app/frontend/src

echo "✅ Проверка импортов shared/contracts..."
grep -r "from.*@shared/contracts\|use.*SharedContracts" app/frontend/src app/backend/src
🔄 Миграция существующего кода
План миграции
Этап 1: Определить все существующие типы

bash
# Найти все enum/const в проекте
find app/ -name "*.php" -o -name "*.ts" -o -name "*.tsx" | xargs grep -h "const.*=" | sort | uniq
Этап 2: Создать primitives/ файлы для найденных типов

Этап 3: Заменить строковые литералы на импорты

Этап 4: Обновить тесты

Пример миграции
До:

php
// app/backend/src/Domain/Tasks/Task.php
class Task {
    const STATUS_NEW = 'new';
    const STATUS_IN_PROGRESS = 'in_progress';
    
    private string $status = self::STATUS_NEW;
}
После:

php
use SharedContracts\Status;

class Task {
    private string $status = Status::PENDING;
    
    public function markInProgress(): void {
        $this->status = Status::PROCESSING;
    }
}
🤝 Соглашения команды
Принятие решений
Изменение существующего примитива → требует approve 2 senior разработчиков

Добавление нового примитива → тикет + описание в primitives/*.md

Изменение векторов → обязательно обновить тесты

Ветвление
text
feature/add-archive-status/
├── shared/contracts/primitives/Status.md
├── shared/contracts/implementations/php/Status.php
├── shared/contracts/implementations/typescript/status.ts
├── app/backend/src/... (использование нового статуса)
└── app/frontend/src/... (использование нового статуса)
📈 Метрики и мониторинг
Dashboard доступен супер-админам:

Количество примитивов: 5+

Количество векторов: 20+ тестовых случаев

Coverage использования: 95%+ (цель)

Расхождения: 0 (цель)

🔗 Связанные компоненты
OpenAPI спецификация — HTTP контракты

Security векторы — канонизация для подписей

Тесты parity — проверка соответствия

📞 Поддержка
Проблемы с контрактами:

Проверить make validate-contracts

Запустить make generate-contracts

Если проблема осталась — создать issue с тегом [contracts]

Вопросы по добавлению новых типов:

Обсудить на planning meeting

Создать RFC в docs/architecture/decisions/

Реализовать после approval

Итог: Shared Contracts — это основа типобезопасности и согласованности Cabinet.
Один раз определил — везде работает.

text

---

✅ **shared/contracts/README.md** — готов. Полное руководство с:
- Философией и проблематикой
- Структурой каталога
- Рабочими процессами
- Антипаттернами
- Миграционными сценариями
- Инструментами мониторинга
