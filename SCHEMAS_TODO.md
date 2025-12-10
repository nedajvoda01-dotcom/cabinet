# Schemas and validation TODO

Autocontent spec is present in the repo as `AUTOCONTENT_SPEC.md`.  
Next step: pull required schemas into backend validation and WS payload checks.

## What to extract and where to place
Create/extend `Schemas.php` (or equivalent validator classes) inside each module and add WS payload validators.

### Modules / pipelines
- **Parser**
  - push request/response schemas  
  - poll request/response schemas  
  - ack payload schema  
  - `pipeline.stage` WS payload schema for parser stage

- **Photos**
  - mask/process request/response schemas  
  - `pipeline.stage` WS payload schema for photos stage

- **Export**
  - export start/output request/response schemas  
  - `pipeline.stage` WS payload schema for export stage

- **Publish**
  - publish/cancel request/response schemas  
  - `pipeline.stage` WS payload schema for publish stage

- **Robot**
  - session start/stop/publish/status request/response schemas  
  - robot-status / publish-status WS payload schemas

### Core domain modules
- **Cards**
  - card create/update/read schemas  
  - card-status WS payload schema

- **Auth**
  - login/refresh schemas  
  - normalized error format schema (shared)

- **Users**
  - user CRUD schemas  
  - validation error schemas

- **Admin**
  - queue list / DLQ list schemas  
  - admin WS payloads (if defined in spec)

## Validation rules to enforce
1. **Fail-fast validation at adapters** (already added):  
   - request validated before sending  
   - response validated right after receiving  
   - mismatch => `AdapterException` with `fatal=true`, code `contract_mismatch`

2. **Module-level validation**
   - Controllers validate inbound HTTP payloads against module schemas.
   - Workers validate job payloads before processing.

3. **WS validation**
   - Every emitted WS event must be validated against its schema before send.

## Definition of done
- All listed schemas extracted from `AUTOCONTENT_SPEC.md`
- Validators wired in Controllers / Workers / WS emitter paths
- Consumer contract tests cover:
  - valid fixture passes
  - invalid fixture => fatal contract_mismatch
- CI includes contract test suite
