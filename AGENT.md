# AGENT.md — CABINET INTERNAL SYSTEM GOVERNANCE (MANDATORY)

This repository is an **internal control-plane / orchestration system**.
Any AI agent (Codex, automation, assistants) must follow the rules below.
**Creativity, assumptions, and architectural improvisation are forbidden.**

---

## 0) Sources of Truth (DO NOT DIVERGE)

1) **STRUCTURE.txt** — authoritative project tree and layer locations.
2) **/shared/contracts/** — authoritative cross-language primitives + vectors.
3) **/security/**, **SECURITY-IMPLEMENTATION.md**, **ENCRYPTION-SCHEME.md**, **HIERARCHY-GUIDE.md** — authoritative security model.
4) Existing unit tests under `app/backend/tests/Unit/Architecture/*` are **hard constraints**.

If any rule conflicts with a source of truth → **source of truth wins**.

---

## 1) Non-goals (What Cabinet is NOT)

Cabinet is NOT:
- a public SaaS / self-service product
- a business-logic engine
- a place to invent domain semantics
- a playground for refactors

Cabinet is:
- a secure orchestration layer
- a pipeline/state-machine executor
- an integration router (ports/adapters)
- an observable, auditable control plane

---

## 2) Absolute Rules (MUST / MUST NOT)

### 2.1 Architecture boundaries (hard)
MUST:
- Keep strict layer separation: `Domain` / `Application` / `Infrastructure` / `Http`.
- Put code only in its correct layer and module.

MUST NOT:
- Move/rename layer folders.
- Introduce new architectural layers.
- Mix infrastructure concerns into Domain/Application.
- Make Domain depend on Http or Infrastructure.
- Add “helpers” that bypass policies/preconditions.

### 2.2 Security (hard)
MUST:
- Treat all networks as hostile; all callers as untrusted by default.
- Apply security requirements per endpoint via `RouteRequirementsMap`.
- Fail closed: if requirements are missing/unclear → deny.

MUST NOT:
- Bypass Nonce/Signature/Encryption enforcement.
- Introduce alternative crypto/canonicalization schemes.
- Log sensitive data (keys, raw payloads, secrets, PII).

### 2.3 Pipeline execution (hard)
MUST:
- Keep pipeline deterministic and resumable.
- Ensure jobs are idempotent and payloads immutable.
- Use locks where parallel execution can corrupt state.
- Classify errors and apply deterministic retry / DLQ rules.

MUST NOT:
- Make workflow decisions inside workers/drivers (no “business brains”).
- Use events to control flow (events are for observability only).

### 2.4 Integrations & fallbacks (hard)
MUST:
- Implement integrations via **Port (Application) → Adapter (Infrastructure)**.
- Provide **Real + Fallback** adapters for each integration.
- Use shared integration utilities (circuit breaker, contract validation, signed/encrypted requests).

MUST NOT:
- Add an integration call that skips validation/signing/encryption rules.
- Treat fallbacks as mocks: fallbacks are minimal functional degradations.

### 2.5 Contracts (hard)
MUST:
- Treat `/shared/contracts` as the only place where “contracts” live.
- Keep PHP/TS implementations aligned with primitives and vectors.
- Update parity tests if contracts change.

MUST NOT:
- Redefine contract primitives elsewhere.
- Manually edit generated API types without updating generation source.

### 2.6 Frontend (hard)
MUST:
- Desktop-only (no mobile layouts, no responsive breakpoints).
- Reflect backend permissions (visibility ≠ authorization).
- Use runtime security (canonicalization/sign/encrypt/nonce/key exchange).

MUST NOT:
- Add role-specific duplicate screens (single UI, reduced projection).
- Bypass backend security.

---

## 3) Working Protocol (How the agent must operate)

### Step A — Locate sources
- Read the relevant module docs + existing patterns in the same folder.
- Use STRUCTURE.txt to validate placement.

### Step B — Change minimal surface
- Prefer the smallest change that fits existing patterns.
- Keep changes localized to the correct layer.

### Step C — Prove correctness
- Add/update tests when behavior changes.
- Keep architecture tests passing.
- Ensure contract parity is not violated.

### Step D — Output discipline
Every code change must include:
- What changed (1–3 bullets)
- Why (1 bullet)
- Files touched (list)
- Risks / rollback notes (if security/pipeline/integrations affected)

---

## 4) Stop Conditions (When the agent MUST stop and ask)

The agent must stop if:
- A requested change requires moving files across layers.
- A requested change alters crypto/canonicalization formats.
- Endpoint requirements are unclear.
- Pipeline transitions are undefined.
- Contract primitives/vectors are missing for a new cross-language type.

---

## 5) Layer quick map (reference)

- `Domain/` → entities, value objects, invariants, allowed transitions.
- `Application/` → commands/queries, policies/preconditions, pipeline orchestration, ports.
- `Infrastructure/` → adapters (PDO/Redis/S3/HTTP), crypto, queue, observability, ws, background jobs.
- `Http/` → routing/controllers/middleware/validation/responses + security pipeline enforcement.

---

## 6) Structure Reference

**STRUCTURE.txt is the authoritative tree.**
Do not paste the full tree here. Keep it in STRUCTURE.txt only.

---

## Final Statement

Cabinet is a **control plane**.  
If unsure: **stop**.  
If it violates rules: **do not proceed**.
