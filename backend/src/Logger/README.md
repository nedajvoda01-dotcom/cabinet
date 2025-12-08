# Logger layer

Единый инструмент логирования и аудита.

## Responsibilities
- Write system logs to DB (system_logs table)
- Provide correlation_id support
- Normalized levels: info|warn|error
- Used by Workers, QueueService, Controllers (for audit actions)

## Rules
- Logger is infra, no business logic
- Adapters do not log business events (only throw AdapterException)
