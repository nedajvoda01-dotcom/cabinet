# DB layer

Инфраструктурный слой хранения.

## Responsibilities
- Provide PDO connection factory
- Safe transactions helper
- Migration runner + versioning
- Base repositories for Modules

## Rules
- Modules use repositories or Models, but do not open PDO directly in Controllers.
- Adapters do not touch DB.
- Workers may use Modules/Repositories.

## Tables (core)
- queue_jobs / dlq_jobs
- cards
- photos
- exports
- publish_jobs
- parser_payloads
- users / user_roles
- system_logs
