# Application Layer

The Application layer defines the **behavioral core** of the Cabinet backend.

This layer describes *what the system does* without specifying *how it is implemented*. It orchestrates use cases, enforces decision rules, coordinates pipelines, and connects the domain to infrastructure through stable interfaces.

The Application layer contains no HTTP code and no infrastructure-specific logic.

---

## Responsibilities

The Application layer is responsible for:

- Defining use cases through commands and queries
- Enforcing access decisions and operational rules
- Coordinating pipelines and background processing
- Orchestrating security-related decisions
- Declaring integration ports
- Providing application services used by controllers

It never performs I/O directly and never depends on concrete infrastructure implementations.

---

## Core Concepts

### Commands

Commands represent **intent to change state**.

Examples:
- authenticate a user
- create or update a task
- trigger pipeline stages
- perform administrative actions

Commands are explicit, validated, and side-effect free until executed.

### Queries

Queries represent **intent to read state**.

They:
- never mutate data
- may use optimized read models
- are safe to cache
- are permission-aware

---

## Policies

Policies define **decision logic**.

They answer questions like:
- is this action allowed?
- should execution be degraded?
- are limits exceeded?
- is access permitted by hierarchy?

Policies do not execute actions.  
They only return decisions.

---

## Preconditions

Preconditions are **fail-fast guards**.

They validate:
- structural correctness
- invariants that must hold before execution
- input constraints that cannot be recovered from

If a precondition fails, execution stops immediately.

---

## Security Services

The Application layer defines interfaces for security-related services:

- nonce management
- key management
- signature verification
- encryption orchestration

Concrete cryptographic logic lives in Infrastructure.

---

## Integrations

Each integration is defined by a **port** in this layer.

Ports:
- describe required capabilities
- are technology-agnostic
- expose no transport-level details

This allows integrations to be replaced or mocked without affecting application logic.

---

## Pipeline Orchestration

Pipelines coordinate asynchronous workflows:

- stages
- jobs
- retries
- locks
- workers

The Application layer defines pipeline intent and orchestration rules.

Implementation details are delegated to Infrastructure.

A dedicated document exists:
- `Pipeline/README.md`

---

## Application Services

Services group related application logic:

- authentication flows
- task coordination
- integration status aggregation
- security orchestration

They are stateless where possible and deterministic by design.

---

## Design Rules

- No infrastructure dependencies
- No HTTP concepts
- No static state
- No hidden side effects
- No business interpretation of data

All dependencies are injected via interfaces.

---

## Extension Guidelines

When adding new functionality:

1. Start with a command or query
2. Add policies if decisions are required
3. Add preconditions if fail-fast validation is needed
4. Define integration ports if external systems are involved
5. Extend pipelines only when async behavior is required

---

## Status

The Application layer is intentionally conservative.  
Its primary goal is long-term stability and predictability.
