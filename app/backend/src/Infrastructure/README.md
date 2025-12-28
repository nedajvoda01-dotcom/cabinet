# Infrastructure Layer

The Infrastructure layer contains **all concrete implementations** used by the Cabinet backend.

This layer is responsible for interacting with the outside world: databases, queues, external services, cryptography providers, observability systems, and background execution environments.

It is the **only layer** allowed to depend on external technologies.

---

## Responsibilities

The Infrastructure layer is responsible for:

- Persistence and data storage
- Queueing and background execution
- External service integrations
- Cryptography and key management
- Runtime security enforcement
- Observability (logging, metrics, tracing)
- Background maintenance tasks

Infrastructure implements interfaces declared in the Application layer.

---

## Layer Boundaries

Infrastructure **must not**:

- Contain business decision logic
- Define authorization rules
- Interpret domain data
- Expose HTTP controllers
- Call Application services directly without explicit orchestration

All behavior is driven by higher layers.

---

## Subsystems Overview

### Persistence

Handles all storage concerns:
- relational databases
- repositories
- migrations
- transactional boundaries

Repositories are deterministic and side-effect free.

---

### Queue and Background Execution

Provides:
- job queues
- dead-letter queues
- metrics
- workers
- schedulers

Execution is asynchronous, observable, and idempotent.

---

### Integrations

External systems are connected here.

Each integration consists of:
- a real adapter
- one or more fallback adapters
- shared integration utilities
- registry and capability descriptors

Integration logic never leaks into Application or Domain layers.

See:
- `Integrations/README.md`

---

### Runtime Security

Implements all cryptographic and security mechanisms:

- encryption and decryption
- signatures and verification
- nonce storage and validation
- key rotation and exchange
- vault and secret injection
- attack protection

Security behavior is explicit and enforced centrally.

See:
- `Security/README.md`

---

### Observability

Infrastructure provides full observability:

- structured logging
- security audits
- metrics (business and technical)
- distributed tracing
- health checks

Observability never alters execution flow.

---

### Background Tasks

Background tasks perform:
- maintenance
- cleanup
- key rotation
- data compaction
- queue optimization

They are isolated from pipelines and do not affect user-facing flows.

---

## Failure Model

Infrastructure is designed to fail safely:

- external failures are isolated
- retries are controlled
- fallbacks prevent pipeline collapse
- security failures fail closed

State corruption is unacceptable.

---

## Extension Rules

When adding new infrastructure components:

1. Ensure an interface exists in Application
2. Keep implementations replaceable
3. Make failures explicit
4. Add observability
5. Avoid hidden coupling

---

## Design Guarantees

- Explicit dependencies
- Replaceable implementations
- Deterministic behavior
- Centralized security enforcement
- Full observability

---

## Status

Infrastructure evolves as integrations and scale requirements grow,  
while preserving strict separation from business logic.
