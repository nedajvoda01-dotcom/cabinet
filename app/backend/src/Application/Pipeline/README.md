# Pipeline Orchestration — Asynchronous Execution Engine

## Location

app/backend/src/Application/Pipeline/README.md

---

## Purpose

The Pipeline subsystem is the **asynchronous execution engine** of Cabinet.

It exists to:
- execute long-running operations
- coordinate multi-stage workflows
- isolate failures
- guarantee determinism and recoverability

The pipeline does **not** contain business logic.
It executes **pre-approved intent** defined by the Application layer.

---

## Core Philosophy

The pipeline answers the question:

> “How do we execute this safely, asynchronously, and reliably?”

Key properties:
- deterministic
- idempotent
- stage-based
- lock-protected
- retry-aware
- observable

If execution is interrupted, the pipeline must be able to **resume**.

---

## High-Level Structure

Pipeline/
├── Drivers/ → Stage execution logic
├── Workers/ → Background executors
├── Jobs/ → Job definitions
├── Retry/ → Retry and backoff rules
├── Locks/ → Concurrency protection
├── Idempotency/ → Duplicate execution prevention
├── Events/ → Pipeline events
└── README.md → This document

yaml
Копировать код

Each component has a single responsibility.

---

## Stages

A pipeline is composed of **explicit stages**.

Examples:
- Parse
- Photos
- Publish
- Export
- Cleanup

Rules:
- stage order is predefined
- transitions are explicit
- skipping stages is forbidden unless defined
- stages are not inferred dynamically

Stage rules live in the Domain layer.
Execution is coordinated here.

---

## Drivers

Drivers implement **how a stage is executed**.

Rules:
- one driver per stage
- drivers are stateless
- drivers are deterministic
- drivers call integrations via ports

Drivers do not:
- decide stage transitions
- schedule jobs
- manage retries

Drivers only execute **one stage**.

---

## Workers

Workers are background processes that:
- fetch jobs from the queue
- acquire locks
- execute drivers
- report results

Rules:
- workers are replaceable
- workers are horizontally scalable
- workers are crash-tolerant
- workers never bypass locks or idempotency

Workers do not know business context.

---

## Jobs

Jobs represent **units of work**.

A job includes:
- task identifier
- stage identifier
- attempt number
- metadata

Rules:
- jobs are immutable
- jobs are idempotent
- jobs can be retried
- jobs can be dead-lettered

Jobs are never executed inline.

---

## Idempotency

Idempotency guarantees:
- no stage executes twice
- retries do not duplicate effects
- partial failures are safe

Rules:
- idempotency keys are mandatory
- idempotency storage is atomic
- idempotency is enforced per stage

If idempotency fails — execution stops.

---

## Locks

Locks prevent:
- parallel execution of the same stage
- race conditions
- double scheduling

Rules:
- locks are explicit
- locks are scoped
- locks have TTL
- locks are released deterministically

No implicit locking is allowed.

---

## Retry Strategy

Retry logic is explicit and controlled.

Includes:
- retry limits
- backoff strategy
- error classification
- DLQ routing

Rules:
- not all errors are retriable
- retry decisions are deterministic
- retries are observable

Infinite retries are forbidden.

---

## Events

Pipeline emits events for:
- stage started
- stage completed
- stage failed
- retries
- pipeline completion

Events are used for:
- WebSocket updates
- metrics
- audit logs
- projections

Events do not alter execution flow.

---

## Failure Model

Failures are expected.

Pipeline guarantees:
- failures are captured
- failures are classified
- failures are observable
- system does not collapse

Fallbacks may activate at integration level.
Pipeline continuity must be preserved.

---

## Forbidden Practices

The pipeline MUST NOT:
- make authorization decisions
- infer business intent
- bypass security
- mutate domain state arbitrarily
- execute synchronous logic

If something must “decide” — it belongs to Application.

---

## Audience

This document is written for:
- backend engineers
- infrastructure engineers
- SREs
- auditors
- AI agents

---

## Summary

The Pipeline is the **execution backbone** of Cabinet.

It runs work safely.
It recovers from failure.
It never guesses.
It never assumes.

If work is running — it is controlled, tracked, and recoverable.
