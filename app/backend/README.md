# cabinet/app/backend/README.md — Backend Entry Point & System Boundaries

## Location

cabinet/app/backend/README.md

---

## Purpose

This document defines the **backend system as a whole**.

It explains:
- what the backend is responsible for
- how responsibilities are divided internally
- where the system boundaries are
- how the backend should be reasoned about

This README is the **entry point into backend architecture**.

It does **not** describe business logic details or code-level behavior.

---

## Role of the Backend in Cabinet

The backend is the **central control plane** of Cabinet.

Its responsibilities are to:
- accept and validate commands
- enforce security and hierarchy
- orchestrate pipelines
- coordinate integrations
- persist state
- expose observability

The backend does **not**:
- implement business intelligence
- make domain-specific decisions for integrations
- contain UI logic
- assume trusted clients

---

## Architectural Style

The backend follows a **strict layered architecture**:

- Domain  
- Application  
- Infrastructure  
- Http (transport)

Each layer has:
- a single responsibility
- explicit dependency rules
- enforced boundaries

Violating layer boundaries is considered a defect.

---

## High-Level Structure

The backend is structured as:

- `public/`  
  HTTP entry point

- `src/`  
  All backend code, strictly layered

- `tests/`  
  Architecture, domain, security, and parity tests

- Configuration and tooling  
  Composer, Docker, QA tools

---

## Execution Model

The backend operates in two modes:

### 1. Synchronous (HTTP)

Used for:
- command submission
- queries
- authentication
- administration
- health checks

Every HTTP request:
- passes through the security pipeline
- is validated structurally
- is authorized explicitly

---

### 2. Asynchronous (Workers)

Used for:
- pipeline stage execution
- integration calls
- retries
- background maintenance

Workers:
- are deterministic
- are idempotent
- do not trust input
- update state atomically

---

## Security Positioning

Security is **not optional** and **not configurable per environment**.

The backend:
- enforces encryption and signatures
- validates nonces and idempotency
- applies scope and hierarchy checks
- audits all security-relevant actions

Security enforcement happens **before** any business logic.

---

## Persistence Model

The backend owns:
- authoritative state
- execution history
- audit logs

Persistence is:
- explicit
- transactional
- versioned where required

Read models and projections exist only for querying convenience.

---

## Integration Model

External systems are treated as:
- untrusted
- unreliable
- replaceable

The backend:
- communicates via ports
- enforces contracts
- applies fallbacks automatically
- never embeds external logic

Integrations are coordinated, not trusted.

---

## Testing Philosophy

Backend tests enforce:
- architectural boundaries
- contract parity
- security invariants
- deterministic behavior

Tests are not optional documentation —  
they are **enforcement mechanisms**.

---

## What This Document Is Not

This README does **not**:
- describe individual services
- list all endpoints
- explain crypto algorithms
- document pipeline internals

Those concerns live in **lower-level READMEs**.

---

## Relationship to Other Documents

Read next:
- `app/backend/src/README.md` — layer responsibilities
- `SECURITY-IMPLEMENTATION.md` — runtime security
- `ENCRYPTION-SCHEME.md` — cryptographic model

This file exists to **orient**, not to instruct line-by-line.

---

## Final Statement

The backend is the **authoritative execution environment** of Cabinet.

If something bypasses the backend — it is invalid.  
If something weakens backend guarantees — it is rejected.  
If something is unclear — **stop and escalate**.
