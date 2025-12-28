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

## Migration notes
- Current schemas are temporary placeholders and will evolve during the conveyor migration.
- The target model is the normalized contract set defined in AGENT1; schemas must converge toward those contracts rather than bespoke shapes.
- Schemas will be rebuilt incrementally alongside migration milestones, not front-loaded in a big bang.
- Any new schemas must remain compatible with the conveyor model (normalized inputs/outputs, capability-driven behavior, traceable execution).
- Legacy schemas are frozen and must not be extended; all new or updated schemas apply only to non-legacy paths, leaving `_legacy` shapes untouched until the code is removed.
- Schemas should increasingly emit the normalized primitives (`Status`, `Error`, `ErrorKind`, `TraceContext`), replacing bespoke error/status shapes over time. Existing modules should be rewritten gradually to return normalized contracts, while legacy schemas remain frozen.
- Pipeline payload shapes must converge on the normalized job contracts introduced in Step 5 (Job/JobPayload/TraceContext/idempotency). Future WS event schemas for job lifecycle (queued/retrying/dlq) should align with these primitives while keeping legacy queue payload schemas frozen.
- Parser ingestion/attachment responses (raw photo refs produced by the ingestion service) need schema coverage when the parser module is normalized so the returned asset refs align with the contract primitives.
- With all module dispatchers now using the pipeline (parser/photos/publish/robot-status), job payload schemas should be expressed against the normalized Job/TraceContext structure rather than per-queue helper shapes; traceId/idempotency fields must be first-class in those schemas.
- Add DLQ record schema and job lifecycle retry metadata (attempt counts, backoff timestamps) so reliability payloads match the pipeline primitives introduced in Step 8; keep legacy DLQ table shape frozen while new schema describes normalized fields (job type/subject, traceId, idempotencyKey, error.kind, failedAt).
