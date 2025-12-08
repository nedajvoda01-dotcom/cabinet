# Work Plan for Autocontent Tasks (Audit → Spec Completion → Refactor → Tests → Docs)

## Scope
User requested sequential execution: audit, spec completion, refactor, full test fixes, queue hardening, and API/WS documentation. Plan aligns with AGENTS.md boundaries and Autocontent Spec.htm as source of behavior.

## Steps
1. **Repository Audit**
   - Read `Autocontent Spec.htm` and map required modules, state machines, retries, WS events, and validation schemas.
   - Inspect backend structure (`backend/src`) for boundary violations among Modules, Adapters, Workers, Controllers.
   - Identify gaps vs Spec: missing statuses, retry/DLQ branches, WS events, schemas.
   - Produce P0/P1/P2 issue list with file paths and concrete fixes.

2. **Spec-Max Implementation (Modules & Workers)**
   - For each Module/Worker pipeline, ensure all state-machine statuses, retry/dlq branches, WS notifications, and request/response validation schemas exist per Spec.
   - Add tests (unit/integration/e2e) that assert status transitions, retries, DLQ routing, and WS emissions.

3. **Refactor Backend (`backend/src`)**
   - Deduplicate repeated logic into private helpers/utilities following existing patterns.
   - Normalize pipeline status values and error formats across Modules/Workers/Controllers.
   - Verify DTO/model typing and align with Spec schemas.
   - Update/extend tests to lock behavior.

4. **Queue/DLQ/Retry Hardening**
   - Review `QueueService`, Workers, retry policy, and DLQ handlers for idempotency and observability.
   - Add metrics/logs/WS events where missing; add integration/e2e coverage.

5. **Testing & Fixes**
   - Run `npm run test:all`, `phpunit tests/unit/backend`, `phpunit tests/integration`, and `npm run test:e2e`.
   - Fix failures; expand tests for Spec coverage where lacking.

6. **Documentation**
   - Generate/align `docs/api-docs/openapi.yaml` with actual routes/controllers/schemas.
   - Update `docs/api-docs/ws-events.md` with full WS emitter events.
   - Add consistency tests for docs vs implementation if feasible.

## Notes
- Maintain strict layer boundaries (Modules ↔ Adapters ↔ Workers ↔ Controllers) per AGENTS.md.
- Propagate `correlation_id` through pipelines, queues, logs, and WS messages.
- No new top-level folders; follow existing naming/styling patterns.
