# Application Pipeline

The Pipeline subsystem defines how the Cabinet backend coordinates **long-running, asynchronous, and multi-stage workflows**.

Pipelines are used whenever an operation:
- cannot be completed synchronously
- involves multiple external systems
- must be retryable, resumable, and auditable
- must survive partial failures

The pipeline does not understand business meaning.  
It only enforces execution order, guarantees consistency, and manages state transitions.

---

## Core Idea

A pipeline represents a **deterministic state machine**.

Each pipeline instance:
- has a current stage
- produces jobs
- may retry on failure
- emits events
- advances only through valid transitions

At no point does the pipeline infer *why* something happens — it only enforces *what is allowed to happen next*.

---

## Main Components

### Stages

Stages represent logical steps in a workflow.

Examples:
- parsing
- photo processing
- publishing
- exporting
- cleanup

Stage transition rules are strict and validated.

---

### Jobs

Jobs are concrete execution units produced by stages.

Properties:
- immutable payload
- idempotent execution
- explicit job type
- traceable lifecycle

Jobs never carry implicit context.

---

### Workers

Workers execute jobs asynchronously.

Characteristics:
- single responsibility
- explicit job type handling
- stateless execution
- safe retries

Workers do not decide flow — they only execute.

---

### Drivers

Drivers connect pipeline stages to integrations.

They:
- translate pipeline intent into integration calls
- return normalized results
- never perform access decisions

Drivers are replaceable and testable.

---

### Idempotency

Every pipeline execution enforces idempotency:

- duplicate commands do not produce duplicate effects
- job replays are safe
- external side effects are guarded

Idempotency is mandatory for all stages.

---

### Retry and Error Classification

Failures are classified explicitly:

- transient
- permanent
- integration-related
- security-related

Retry policies are deterministic and configurable.

There is no infinite retry.

---

### Locks

Locks prevent:
- concurrent execution of incompatible stages
- race conditions
- duplicated processing

Locks are scoped and time-bound.

---

### Events

The pipeline emits events for:

- stage transitions
- job execution results
- failures and retries
- completion

Events are used for observability and audit, not control flow.

---

## Failure Model

The pipeline is designed to **degrade gracefully**.

If an integration fails:
- fallback adapters may be used
- retries are attempted when appropriate
- pipeline state remains consistent

The pipeline never corrupts state.

---

## What Pipelines Do NOT Do

- They do not interpret business data
- They do not perform authorization
- They do not access infrastructure directly
- They do not modify domain rules
- They do not dynamically change topology

---

## Extension Rules

When adding a new pipeline stage:

1. Define the stage and allowed transitions
2. Define job types
3. Implement drivers
4. Implement workers
5. Define retry and idempotency behavior
6. Add observability hooks

Every step must be explicit.

---

## Design Guarantees

- Deterministic execution
- Auditable state transitions
- Safe retries
- Clear failure boundaries
- Replaceable integrations

---

## Status

The pipeline subsystem is a critical backbone of the platform.  
Changes must preserve backward compatibility and deterministic behavior.
