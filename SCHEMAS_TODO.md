# Schemas and validation TODO

Pull remaining schemas from `AUTOCONTENT_SPEC.md` into module Schemas.php files and WS payload validators.

- Parser: push/poll request & response schemas, ack payload schema, pipeline.stage WS payload.
- Photos: mask/process request/response schemas, pipeline.stage WS payload.
- Export: export start/output schemas, pipeline.stage WS payload.
- Publish: publish/cancel request/response schemas, pipeline.stage WS payload.
- Robot: session start/stop/publish/status schemas, robot-status WS payload.
- Cards: card create/update/read schemas and card-status WS payload.
- Auth: login/refresh schemas and error format alignment.
- Users: user CRUD schemas with validation errors.
- Admin: queue/dead-letter listing schemas and admin WS payloads if any.
