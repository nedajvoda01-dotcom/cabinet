# Application Pipeline — Execution Orchestration

## Location

app/backend/src/Application/Pipeline

---

## Purpose

The Application Pipeline is the **deterministic execution engine** of Cabinet.

It is responsible for:
- orchestrating asynchronous work
- enforcing execution order
- managing retries and failures
- guaranteeing idempotency
- coordinating workers and jobs
- emitting execution events

The pipeline **does not implement business logic**.
It executes **predefined stages** under strict rules.

---

## Status

**Status: Frozen**

The pipeline execution model is considered **stable and sealed**.

Any change to:
- stage ordering
- retry semantics
- locking behavior
- idempotency guarantees

requires **explicit architectural approval**.

---

## Core Characteristics

The pipeline is:

- Deterministic  
- Idempotent  
- Lock-protected  
- Retry-aware  
- Event-driven  
- Asynchronous  

Execution is **state-based**, not condition-based.

---

## Pipeline Model

### Stages

Work is executed as a sequence of **explicit stages**.

Each stage:
- has a single responsibility
- is executed by a worker
- produces a state transition
- is repeatable without side effects

Stage transitions are governed by:
- `StageTransitionRules`
- persisted pipeline state
- idempotency constraints

---

### Jobs

A job represents a **unit of pipeline execution**.

Jobs:
- are persisted
- are resumable
- may be retried
- may be force-cleaned by administrators

A job never “disappears”.
It always terminates in a **final state**.

---

### Workers

Workers:
- poll jobs
- execute exactly one stage at a time
- report results back to the pipeline

Workers are:
- stateless
- replaceable
- horizontally scalable

---

## Retry & Failure Model

Retries are:
- explicit
- bounded
- deterministic

Failures:
- do not corrupt state
- do not skip stages
- do not execute out of order

Fallback behavior is handled **outside** the pipeline,
via integration adapters.

---

## Events & Observability

The pipeline emits events for:
- stage start
- stage completion
- failure
- retry
- cancellation

Events exist for:
- auditing
- monitoring
- debugging
- replay analysis

---

## Non-Goals (Critical)

The pipeline MUST NOT:

- interpret payload semantics
- branch based on business meaning
- apply heuristics or optimizations
- embed domain rules
- skip stages conditionally
- modify execution order dynamically

If logic requires interpretation — it **does not belong here**.

---

## Enforcement

Pipeline guarantees are enforced by:
- idempotency storage
- lock services
- retry policies
- architecture tests
- invariants in code

Violation of these rules is considered a **system defect**.

---

## Summary

The Application Pipeline is a **pure orchestration mechanism**.

It executes.
It coordinates.
It enforces order.

It does not decide.
It does not interpret.
It does not “optimize”.

If behavior is unclear — execution stops.
