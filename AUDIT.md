# Repository Audit (Step 1)

## Boundary violations (P0/P1/P2)
- **P0 – Business logic in adapter** — `backend/src/Adapters/ParserAdapter.php` performs photo download/order calculation and S3 writes inside the adapter (`ingestRawPhotos`), breaching the rule that adapters must only handle transport/translation, no business logic. Move ingestion orchestration into a module/service or worker and leave the adapter as a thin IO layer.【F:backend/src/Adapters/ParserAdapter.php†L35-L58】【F:AGENTS.md†L227-L236】【F:AGENTS.md†L289-L296】
- **P1 – Frozen tree missing required doc/infra nodes** — Expected `docs/`, `infra/`, `.github/workflows`, and other root files from the frozen structure are absent, so repository layout diverges from AGENTS.md. Add the missing directories/files (even as stubs) to match the mandated tree.【F:AGENTS.md†L39-L150】【01b54d†L1-L3】
- **P1 – Source-of-truth Spec** — **Resolved**: Autocontent spec is present in the repo, enabling alignment of implementations/tests with the declared source of truth.

## Gaps vs Autocontent Spec (per module/worker)
- **Parser module** — Queue integration is a TODO; no retry/DLQ handling, idempotency, or WS events are wired in `ParserJobs`, leaving the async path unimplemented. Need real queue dispatch, error semantics, and WS notifications for parse start/progress/fail/done per Spec.【F:backend/src/Modules/Parser/ParserJobs.php†L12-L35】 
- **Export module** — Pipeline states are limited to a short list and stop at `done/failed/canceled`; no explicit retry/DLQ states, no correlation_id propagation, and no WS events around long-running export stages. `ExportJobs` is stubbed with TODOs, so async execution, retries, and DLQ aren’t covered.【F:backend/src/Modules/Export/ExportService.php†L18-L100】【F:backend/src/Modules/Export/ExportJobs.php†L12-L34】
- **Publish module** — Background job dispatch is unimplemented (all TODOs) and lacks retry/DLQ/WS coverage, so publish tasks can’t actually execute via queues nor surface state transitions externally.【F:backend/src/Modules/Publish/PublishJobs.php†L12-L43】
- **Photos module** — Similar to publish/export, `PhotosJobs` is stubbed; missing queue wiring, retries, DLQ flow, and WS events for photo processing steps.【F:backend/src/Modules/Photos/PhotosJobs.php†L12-L35】
- **Robot/Publish worker loop** — `RobotService` handles attempts and status sync but lacks WS emissions and explicit DLQ/retry integration hooks for worker failures; external session lifecycle errors only update DB without queue policies or Spec-driven state transitions.【F:backend/src/Modules/Robot/RobotService.php†L15-L118】
- **Cross-cutting** — No WS event matrix or schema validation is present for modules; validation relies on DTO parsing without schemas tied to Spec. Add validation schemas for request/response and WS payloads across modules in line with Autocontent Spec requirements.【F:AGENTS.md†L25-L36】

## Test gaps
- There are no unit/integration/e2e tests covering Robot flows, WS events, DLQ/retry semantics, or queue adapters; existing suites only touch parser/photos/export/publish happy paths. Add tests that exercise retries, DLQ routing, WS notifications, and Robot publish status sync per Spec across unit/integration/e2e layers.【db0085†L1-L16】
- No structural/tests enforcement for frozen tree or boundary linting is present; CI expectations in AGENTS.md (lint + boundary-lint) aren’t represented, risking unnoticed violations. Add boundary/lint tests or CI checks to enforce the mandated structure and dependencies.【F:AGENTS.md†L192-L200】【01b54d†L1-L3】

## Quick wins (≤1h)
- Quick wins tracked in Layers 1–3 are completed (frozen-tree placeholders, spec availability, queue dispatch stubs replaced with real wiring).
