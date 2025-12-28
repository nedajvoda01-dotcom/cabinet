# Cabinet — Internal Control & Orchestration Platform

Cabinet is an **internal control plane and orchestration system**.

It is designed to securely accept commands, enforce authorization,
and orchestrate execution across multiple external and internal services
without embedding business-specific intelligence into the core.

Cabinet is a **platform component**, not an end-user product.

---

## What Cabinet Is

Cabinet is:

- an internal orchestration and execution control system
- a secure command routing layer
- a pipeline-based task executor
- an integration hub for external services
- an auditable, observable control plane

Cabinet exists to ensure that execution across systems is:

- authorized
- ordered
- resilient
- observable
- recoverable
- secure by default

Cabinet **does not care** what business logic is executed.
It only guarantees that execution happens **correctly and safely**.

---

## What Cabinet Is NOT

Cabinet is **not**:

- a public SaaS
- a self-service platform
- a business-logic engine
- a workflow designer
- a place to encode domain intelligence
- a UI-driven system of record

If a feature requires understanding *what* the data means,
it likely does **not** belong in Cabinet.

---

## Core Philosophy

### Frozen Core

The core of Cabinet is intentionally **frozen**.

This includes:
- security model
- pipeline execution logic
- retry and failure semantics
- idempotency guarantees
- locking and concurrency rules

The core should remain stable and predictable.

---

### Extensibility via Integrations

All extensibility happens **outside** the core via integrations.

Cabinet connects to:
- external services
- internal tools
- automation systems
- analytical or processing engines

through **explicit ports and adapters**.

Cabinet orchestrates.
Integrations execute.

---

## Access & Usage Model

Cabinet is an **internal system**.

- Users cannot self-register and start using the system.
- Registration creates an **access request**, not an account.
- Access requests require **manual approval** by a Super Admin.

All users share:
- a single interface
- a single execution model

Differences between users are enforced via:
- hierarchy
- scopes
- permissions
- visibility filtering

There are no role-specific UIs.

---

## High-Level Architecture

Cabinet is composed of the following major parts:

- **Backend**
  - HTTP boundary
  - security enforcement
  - orchestration and pipelines
  - integrations and infrastructure

- **Frontend**
  - controlled UI
  - permission-based visibility
  - client-side security participation

- **Shared Contracts**
  - cross-language primitives
  - security vectors
  - generated types

- **Security & Governance**
  - cryptographic schemes
  - hierarchy rules
  - enforcement models

Each part has **strict boundaries**.
Crossing boundaries incorrectly is considered a defect.

---

## Security First

Security in Cabinet is:

- mandatory
- layered
- fail-closed
- enforced structurally, not optionally

No request reaches application logic without passing
the full security pipeline.

Security is not configurable per developer or environment.

---

## Documentation Map

This repository contains structured documentation.

Start here, then follow the index:

- `docs/README.md` — documentation index and reading order

Normative security documents:
- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`
- `HIERARCHY-GUIDE.md`

Shared contracts:
- `shared/contracts/README.md`

Each document defines **intent and constraints**, not code duplication.

---

## Source of Truth Rules

There is **no duplicated source of truth**:

- Architecture rules live in code structure and architecture tests
- Runtime security behavior lives in backend infrastructure
- Cross-language data definitions live in `shared/contracts`
- Governance rules live in root-level security documents

If two sources conflict:
> the more **normative** document wins.

---

## Intended Audience

This repository is written for:

- internal developers
- platform engineers
- security engineers
- auditors
- AI agents operating on the codebase

It is **not** written as a tutorial.

---

## Final Note

Cabinet is a **control plane**, not a playground.

If something feels unclear:
- consult documentation
- inspect tests
- follow existing patterns

If something violates documented constraints:
- do not implement it

Predictability, safety, and correctness
are more important than convenience.

---
