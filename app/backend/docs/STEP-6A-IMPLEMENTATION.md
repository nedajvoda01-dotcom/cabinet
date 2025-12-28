# STEP 6A Implementation - SQLite Persistence

## Overview
This implementation replaces the InMemory repositories with SQLite-backed persistence, enabling data to survive process restarts while maintaining the fail-closed security architecture.

## Architecture

### Database Layer
- **ConnectionFactory**: Singleton PDO connection factory
  - Default path: `/tmp/cabinet.db`
  - Configurable via `DB_PATH` environment variable
  - Enables foreign keys and exception mode

- **MigrationsRunner**: Idempotent schema migrations
  - Runs automatically in dev/test environments
  - Can be forced in prod with `RUN_MIGRATIONS=1`
  - Creates 5 tables if they don't exist

### Schema

#### users
```sql
CREATE TABLE users (
    id TEXT PRIMARY KEY,
    role TEXT NOT NULL,
    scopes_json TEXT NOT NULL,
    is_active INTEGER NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
```

#### access_requests
```sql
CREATE TABLE access_requests (
    id TEXT PRIMARY KEY,
    requested_by TEXT NOT NULL,
    status TEXT NOT NULL,
    requested_at TEXT NOT NULL,
    resolved_at TEXT NULL,
    resolved_by TEXT NULL
)
```

#### tasks
```sql
CREATE TABLE tasks (
    id TEXT PRIMARY KEY,
    created_by TEXT NOT NULL,
    status TEXT NOT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
)
```

#### pipeline_states
```sql
CREATE TABLE pipeline_states (
    task_id TEXT PRIMARY KEY,
    stage TEXT NOT NULL,
    status TEXT NOT NULL,
    attempt INTEGER NOT NULL,
    last_error_kind TEXT NULL,
    is_terminal INTEGER NOT NULL,
    updated_at TEXT NOT NULL
)
```

#### idempotency_keys
```sql
CREATE TABLE idempotency_keys (
    actor_id TEXT NOT NULL,
    idem_key TEXT NOT NULL,
    task_id TEXT NOT NULL,
    created_at TEXT NOT NULL,
    PRIMARY KEY (actor_id, idem_key)
)
```

### Repository Implementations

All repositories implement their respective Application port interfaces:

1. **UsersRepository** → `Application\Ports\UserRepository`
   - Stores user role, scopes, active status, timestamps
   - JSON-encodes scopes array

2. **AccessRequestsRepository** → `Application\Ports\AccessRequestRepository`
   - Tracks access request lifecycle
   - Supports pending → approved/rejected transitions

3. **TasksRepository** → `Application\Ports\TaskRepository`
   - Implements idempotency via `idempotency_keys` table
   - Provides `storeIdempotencyKey()` method
   - Supports `findByActorAndIdempotencyKey()` for deduplication

4. **PipelineStatesRepository** → `Application\Ports\PipelineStateRepository`
   - Reconstructs pipeline state from persisted data
   - Handles stage progression and status transitions

5. **PDOUnitOfWork** → `Application\Ports\UnitOfWork`
   - Wraps PDO transactions
   - Tracks transaction state to prevent nested transactions

### Dependency Injection

The `Container` uses environment variable `USE_SQLITE` (default: true) to choose between SQLite and InMemory repositories:

```php
public function userRepository(): UserRepository
{
    if ($this->userRepository === null) {
        $useSqlite = getenv('USE_SQLITE') !== 'false';
        
        if ($useSqlite) {
            $this->userRepository = new UsersRepository($this->pdo());
        } else {
            $this->userRepository = new InMemoryUserRepository();
        }
    }
    return $this->userRepository;
}
```

This allows tests to use InMemory for unit tests and SQLite for integration tests.

## Configuration

### Environment Variables

- `DB_PATH`: SQLite database file path (default: `/tmp/cabinet.db`)
- `USE_SQLITE`: Enable SQLite repos (default: `true`, set to `false` for InMemory)
- `RUN_MIGRATIONS`: Force migrations in production (default: auto in dev/test)
- `APP_ENV`: Environment mode (`dev`, `test`, `prod`)

## Testing

### Integration Tests
Located in `app/backend/tests/Integration/SqlitePersistenceTest.php`:

1. **testRequestAccessPersistsToDatabase**: Verifies access requests are persisted
2. **testCreateTaskPersistsTaskAndPipelineState**: Verifies task and pipeline state persistence
3. **testIdempotencyKeyEnforcesUniqueness**: Verifies idempotency key enforcement
4. **testIdempotencyAcrossProcessRestart**: Verifies idempotency survives process restarts
5. **testApproveAccessCreatesPersistedUser**: Verifies user creation on access approval

### Running Tests
```bash
php app/backend/tests/run.php
```

All 19 test suites pass (128 total tests).

## Migration to PostgreSQL

The SQLite implementation is designed to be easily swappable with PostgreSQL:

1. Create `PostgresConnectionFactory` with similar interface
2. Implement PostgreSQL-specific repositories (SQL syntax differences minimal)
3. Update `Container` to use environment variable to choose database type
4. Schema is portable (minor type adjustments needed: TEXT → VARCHAR, INTEGER → INT)

## Security Considerations

- Database path is configurable but defaults to `/tmp` for development
- Production deployments should set `DB_PATH` to a secure location
- SQLite file permissions should be restricted
- No credentials in SQLite (single-file database)
- Future: Add encryption-at-rest for sensitive data

## Idempotency Implementation

The `idempotency_keys` table ensures exactly-once semantics for task creation:

1. Client sends request with `(actorId, idempotencyKey)`
2. Repository checks for existing mapping
3. If found, returns existing task ID
4. If not found, creates new task and stores mapping atomically
5. Composite primary key prevents duplicates at database level

This survives process restarts and database reconnections.

## Next Steps (STEP 6B)

The SQLite persistence layer is now ready for:
- Job queue implementation with persisted jobs
- Worker runtime for background processing
- Retry policies with exponential backoff
- Dead letter queue for failed jobs

## Files Changed

### Created
- `Infrastructure/Persistence/PDO/ConnectionFactory.php`
- `Infrastructure/Persistence/PDO/MigrationsRunner.php`
- `Infrastructure/Persistence/PDO/PDOUnitOfWork.php`
- `Infrastructure/Persistence/PDO/Repositories/UsersRepository.php`
- `Infrastructure/Persistence/PDO/Repositories/AccessRequestsRepository.php`
- `Infrastructure/Persistence/PDO/Repositories/TasksRepository.php`
- `Infrastructure/Persistence/PDO/Repositories/PipelineStatesRepository.php`
- `tests/Integration/SqlitePersistenceTest.php`

### Modified
- `Bootstrap/Container.php` - Added SQLite repository wiring and migration runner
- `Application/Handlers/CreateTaskHandler.php` - Support TasksRepository for idempotency
- `tests/run.php` - Added integration tests to test suite
