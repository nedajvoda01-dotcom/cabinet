# Integrations — External System Connectivity Layer

## Location

app/backend/src/Infrastructure/Integrations/README.md

---

## Purpose

The Integrations subsystem defines **how Cabinet communicates with external systems**.

These systems include:
- parsers
- robots
- browsers
- storage backends
- media processors
- internal auxiliary services

Cabinet treats **all integrations as untrusted**.

---

## Core Principle

Integrations are **replaceable adapters**, not embedded logic.

Rules:
- Cabinet never depends on integration internals
- Cabinet never trusts integration responses
- Cabinet never blocks execution on integration availability

If an integration fails, Cabinet **continues operating**.

---

## Integration Pattern (Mandatory)

Every integration follows the same structure:

<IntegrationName>/
├── <IntegrationName>Integration.php → Facade / coordinator
├── Real/ → Real adapter (HTTP, SDK, etc.)
├── Fallback/ → Fallback adapter(s)
└── README.md (optional)

yaml
Копировать код

This pattern is enforced across the system.

---

## Components Explained

### Integration Facade

Responsibilities:
- expose integration capabilities
- select real or fallback adapter
- enforce configuration constraints
- normalize errors

The facade is the **only entry point** used by Application layer.

---

### Real Adapters

Real adapters:
- communicate with external systems
- perform signed and encrypted requests
- validate responses
- map errors

Rules:
- must obey security protocol
- must be deterministic
- must never leak raw responses

Real adapters are assumed to be unreliable.

---

### Fallback Adapters

Fallbacks are **not mocks**.

Fallbacks are:
- minimal functional implementations
- used during outages
- used during degradation
- used for safety continuity

Fallbacks must:
- respect contracts
- respect security boundaries
- preserve pipeline flow

Fallbacks must never:
- bypass validation
- fabricate unsafe data
- skip audit events

---

## Shared Integration SDK

All integrations rely on a shared internal SDK:

Integrations/Shared/
├── HttpClient.php
├── SignedRequest.php
├── EncryptionWrapper.php
├── CircuitBreaker.php
├── HealthCache.php
├── ErrorMapper.php
└── ContractValidator.php

yaml
Копировать код

This ensures:
- uniform security
- consistent error handling
- consistent observability
- consistent degradation behavior

No integration may implement its own SDK.

---

## Security Model

Integrations:
- authenticate explicitly
- use scoped permissions
- use signed requests
- use encrypted payloads
- are subject to rate limits

There are **no trusted integrations**.

Fallbacks do not bypass security.

---

## Registry

The integration registry:
- declares available integrations
- manages certificates
- enforces configuration validity
- exposes capabilities

Integrations are registered declaratively.
They are not discovered dynamically.

---

## Failure Handling

Integration failures:
- are classified
- are logged
- are audited
- may trigger retries or fallbacks

Cabinet must never:
- crash on integration failure
- lose pipeline state
- hide failures

---

## Forbidden Practices

Integrations MUST NOT:
- contain business logic
- decide pipeline flow
- mutate domain state directly
- bypass security
- access persistence directly

If logic belongs to Cabinet — it does not belong here.

---

## Audience

This document is written for:
- backend developers
- integration developers
- security engineers
- auditors

---

## Core Responsibilities

Integrations are **tentacles**, not brains.

They connect Cabinet to the outside world.
They fail.
They degrade.
They recover.

Cabinet remains stable regardless.
