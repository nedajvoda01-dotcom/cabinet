# Backend — Cabinet Runtime Core

## Location

app/backend/README.md

---

## Purpose

The backend is the **runtime core** of the Cabinet system.

It is responsible for:
- enforcing security boundaries
- accepting and validating commands
- orchestrating execution pipelines
- coordinating integrations
- persisting state
- providing observability

The backend is **not a business application**.
It is a **secure control plane**.

---

## Architectural Role

The backend sits between:
- external actors (users, integrations, automation)
- internal execution mechanisms (pipeline, workers, storage)

It guarantees that **nothing happens** unless:
- the request is authenticated
- the request is authorized
- invariants are satisfied
- execution is observable and auditable

---

## High-Level Structure

app/backend
├── public/ → HTTP entry point
├── src/ → Backend source code
├── tests/ → Unit and architecture tests
└── README.md → This document

yaml
Копировать код

Each directory has a **strict responsibility**.

---

## Entry Point

### `public/index.php`

This is the **only HTTP entry point**.

Responsibilities:
- bootstrap the application
- initialize the container
- route HTTP requests
- invoke the security pipeline

No logic is allowed here beyond bootstrapping.

---

## Source Code (`src/`)

The backend source code is divided into **explicit layers**:

- `Application`  
  Command handling, orchestration, policies, pipelines.

- `Domain`  
  Pure domain models, invariants, and value objects.

- `Infrastructure`  
  Persistence, integrations, queues, background jobs, security runtime.

- `Http`  
  Controllers, middleware, request validation, security pipeline wiring.

- `Bootstrap`  
  Application startup and dependency wiring.

Each layer is isolated.
Cross-layer shortcuts are forbidden.

---

## Security Model

Security is:
- mandatory
- structural
- fail-closed

All HTTP requests pass through:
- authentication
- nonce validation
- signature verification
- encryption enforcement
- scope and hierarchy checks
- rate limiting

If security fails — execution stops immediately.

---

## Execution Model

The backend:
- does not execute work synchronously
- schedules work via the pipeline
- delegates execution to workers
- tracks state transitions explicitly

Execution is:
- idempotent
- resumable
- observable

---

## Persistence

Persistence is handled via:
- repositories
- explicit transactions
- read models and projections

The backend never:
- exposes raw database access
- leaks persistence details across layers

---

## Observability

The backend provides:
- structured logs
- audit trails
- metrics
- health checks
- tracing

Silent failure is forbidden.

---

## Testing Strategy

Tests include:
- unit tests
- architecture boundary tests
- contract parity tests
- security regression tests

Architecture violations are **build blockers**.

---

## Non-Goals (Critical)

The backend MUST NOT:
- embed business intelligence
- make domain decisions
- trust frontend input
- trust integrations
- bypass security
- optimize for convenience

If logic appears “useful” but violates boundaries — it does not belong here.

---

## Audience

This document is written for:
- backend developers
- system architects
- security engineers
- auditors
- AI agents

It is not an onboarding tutorial.

---

## Summary

The backend is the **enforcement engine** of Cabinet.

It validates.
It orchestrates.
It enforces.
It observes.

It never assumes.
It never trusts.
It never guesses.

If execution occurs — it was explicitly allowed.
