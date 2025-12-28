# Application Layer — Command & Orchestration Boundary

## Location

app/backend/src/Application

---

## Purpose

The Application layer defines **how the system is used**.

It is the boundary between:
- external intent (HTTP, integrations, operators)
- internal execution (Domain, Pipeline, Infrastructure)

This layer:
- accepts commands
- validates intent
- enforces policies
- coordinates execution
- delegates work to the pipeline or services

It does **not** contain business rules.
It does **not** implement infrastructure.
It does **not** expose persistence details.

---

## Responsibilities

The Application layer is responsible for:

- Command handling
- Query handling
- Access and hierarchy enforcement
- Precondition validation
- Policy evaluation
- Orchestration of pipelines
- Coordination of integrations

It answers the question:

> “Is this action allowed, valid, and executable right now?”

---

## Command Model

Commands represent **explicit intent to change state**.

Characteristics:
- immutable
- validated before execution
- authorized before execution
- idempotent where required

Examples:
- create task
- trigger pipeline stage
- retry job
- cancel execution

Commands do not return domain objects.
They return **execution acknowledgements**.

---

## Query Model

Queries represent **read-only intent**.

Characteristics:
- no side effects
- permission-filtered
- projection-based
- optimized for reading

Queries must never:
- mutate state
- trigger pipeline execution
- call integrations

---

## Policies

Policies enforce **system-level rules**.

Policies include:
- access control
- hierarchy constraints
- rate limits
- degradation behavior
- data minimization

Policies:
- are deterministic
- are explicit
- fail closed

Policies are **not configurable by users**.

---

## Preconditions

Preconditions protect **internal consistency**.

They validate:
- payload structure
- references
- stage validity
- data boundaries

Preconditions are:
- not security
- not authorization
- not optional

They execute **after security**, before orchestration.

---

## Services

Application services:
- coordinate multiple actions
- orchestrate calls to domain, pipeline, and integrations
- do not own business rules

Services must remain:
- thin
- explicit
- predictable

If logic grows complex — it belongs elsewhere.

---

## Integrations (Application View)

The Application layer defines:
- ports
- contracts
- expectations

It never depends on:
- real adapters
- HTTP clients
- storage drivers

All external interaction is abstracted.

---

## Non-Goals (Critical)

The Application layer MUST NOT:

- contain business intelligence
- contain persistence logic
- contain cryptography
- implement retries
- bypass security
- encode UI logic

Violations are architectural defects.

---

## Enforcement

Boundaries are enforced by:
- directory structure
- static analysis
- architecture tests
- explicit interfaces

Code outside this layer must not depend on its internals.

---

## Summary

The Application layer is the **control surface** of Cabinet.

It validates intent.
It enforces rules.
It coordinates execution.

It does not decide meaning.
It does not execute work.
It does not store data.

If intent is invalid — execution never begins.
