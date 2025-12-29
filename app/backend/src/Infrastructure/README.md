# Infrastructure — Runtime Implementations & System Integration

## Location

app/backend/src/Infrastructure/README.md

---

## Purpose

The Infrastructure layer contains **all concrete runtime implementations**
required to operate the Cabinet system.

This layer answers the question:

> “How does this actually run in production?”

Infrastructure is where Cabinet touches:
- databases
- queues
- external services
- cryptography
- operating system resources
- background processes

---

## Core Principle

Infrastructure **implements**, but does not **decide**.

Rules:
- No business logic
- No domain rules
- No authorization decisions
- No implicit behavior

Infrastructure follows **contracts defined by Application and Domain layers**.

---

## High-Level Structure

Infrastructure/
├── BackgroundTasks/ → Periodic and maintenance jobs
├── Cache/ → Caching mechanisms
├── Integrations/ → External system adapters
├── Observability/ → Logs, metrics, tracing, health
├── Persistence/ → Database and repositories
├── Queue/ → Job queues and DLQ
├── ReadModels/ → Projections for queries
├── Security/ → Cryptographic and security runtime
├── Ws/ → WebSocket runtime
└── README.md → This document

yaml
Копировать код

Each submodule is **self-contained** and explicit.

---

## BackgroundTasks

Purpose:
- maintenance
- cleanup
- optimization
- rotation

Examples:
- nonce cleanup
- key rotation
- queue optimization
- old data removal

Rules:
- tasks are deterministic
- tasks are idempotent
- tasks are observable
- tasks never bypass security invariants

---

## Cache

Purpose:
- performance optimization
- read-model acceleration
- health state caching

Rules:
- cache is an optimization, not a source of truth
- cache failure must never break execution
- cache invalidation is explicit

---

## Integrations

Integrations are **untrusted external actors**.

Each integration:
- implements an Application Port
- uses signed and encrypted communication
- has a Real adapter
- has a Fallback adapter

Fallbacks are:
- functional
- minimal
- non-breaking
- security-compliant

No integration is ever “trusted”.

---

## Observability

Infrastructure provides:
- structured logging
- audit logging
- metrics export
- distributed tracing
- health checks

Observability is:
- mandatory
- non-optional
- always-on

Silent failure is forbidden.

---

## Persistence

Persistence includes:
- database connections
- repositories
- migrations
- transactions

Rules:
- repositories are the only DB access point
- no raw queries outside repositories
- persistence details never leak upward

---

## Queue

Queue subsystem provides:
- job scheduling
- retries
- dead-letter queues (DLQ)
- metrics

Rules:
- jobs are idempotent
- retries are controlled
- failures are observable
- DLQ is queryable and actionable

---

## Read Models

Read models exist to:
- optimize queries
- decouple reads from writes
- avoid domain leakage

Rules:
- projections are append-only
- projections are rebuildable
- read models do not affect execution

---

## Security (Runtime)

Infrastructure Security includes:
- encryption engines
- signature verification
- nonce storage
- key management
- vault integration
- attack protection

Security here is **mechanical execution**, not policy definition.

Policies live in Application.
Enforcement lives here.

---

## WebSocket Runtime

Provides:
- real-time updates
- pipeline progress events
- status broadcasting

Rules:
- WS never bypasses authorization
- WS messages are derived from events
- WS is optional but consistent

---

## Forbidden Practices

Infrastructure MUST NOT:
- make authorization decisions
- embed domain rules
- infer business meaning
- mutate domain state arbitrarily
- bypass Application layer

If Infrastructure “needs context” — it does not belong here.

---

## Audience

This document is written for:
- backend engineers
- DevOps engineers
- security engineers
- auditors

---

## Core Responsibilities

Infrastructure is the **execution machinery** of Cabinet.

It connects.
It persists.
It encrypts.
It queues.
It observes.

It does not decide.
It does not interpret.
It does not guess.

If Infrastructure runs something — it was explicitly ordered to.
