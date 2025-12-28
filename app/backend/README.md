# Backend Application

This directory contains the backend application of the Cabinet platform.

The backend is responsible for **secure orchestration**, **access control**, **pipeline coordination**, and **integration management**. It intentionally avoids embedding business-specific logic and instead acts as a stable, auditable, and security-focused core.

---

## Backend Responsibilities

The backend performs the following functions:

- Exposes a HTTP API for the frontend and internal services
- Validates authentication, authorization, and protocol correctness
- Enforces security policies and invariants
- Coordinates synchronous and asynchronous workflows
- Orchestrates external integrations through stable ports
- Guarantees pipeline continuity through fallbacks
- Records audit, metrics, and observability data

The backend does **not** interpret business semantics of data flowing through it.

---

## Architectural Layers

The backend is structured into clearly separated layers:

### `public/`
HTTP entry point.  
Contains only the bootstrap file required to route requests into the application.

### `src/Bootstrap`
Application bootstrapping and runtime wiring:
- dependency container
- configuration loading
- clock and environment abstractions
- application kernel

### `src/Http`
All HTTP-facing concerns:
- controllers and routes
- middleware
- request validation
- response formatting
- HTTP-level security pipeline

No business logic lives here.

### `src/Application`
Use cases and orchestration logic:
- commands and queries
- policies and preconditions
- pipeline orchestration
- application-level services
- integration ports

This layer defines *what* should happen, never *how* it is implemented.

### `src/Domain`
Pure domain models and invariants:
- entities
- value objects
- domain rules
- domain-specific exceptions

No infrastructure, no HTTP, no external dependencies.

### `src/Infrastructure`
Concrete implementations:
- persistence
- queues
- background workers
- integrations
- runtime security
- observability

This is the only layer allowed to depend on external systems.

---

## Security Model (High Level)

Security is enforced at multiple layers:

- HTTP security pipeline (authentication, nonces, signatures, rate limits)
- Application policies and preconditions
- Infrastructure cryptography and key management
- Domain-level invariants

Security rules are explicit, testable, and fail-closed.

Detailed security behavior is documented separately:
- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`
- `HIERARCHY-GUIDE.md`

---

## Integrations

External services are connected via **ports defined in the Application layer** and **adapters implemented in Infrastructure**.

Every integration supports:
- a real adapter
- a fallback (fake) adapter

This ensures that pipelines continue operating even when external services are unavailable.

---

## Pipelines and Background Processing

The backend supports long-running and asynchronous workflows through a pipeline model:

- stages and state transitions
- idempotent jobs
- retries and error classification
- workers and background daemons

Pipelines are deterministic and auditable.

---

## Testing Strategy

Backend tests are split by intent:

- `tests/Unit` — fast, deterministic unit tests
- `tests/Feature` — application-level behavior tests
- Root-level `tests/` — integration, e2e, and security tests

Architecture boundaries are enforced through tests.

---

## Running the Backend (Development)

Typical development workflow:

```bash
composer install
docker compose up -d
composer test
