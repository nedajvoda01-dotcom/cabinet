# cabinet/app/backend/src/Infrastructure/Integrations/README.md — Integrations Model (Ports, Adapters, Fallbacks)

## Location

cabinet/app/backend/src/Infrastructure/Integrations/README.md

---

## Purpose

This document defines the **integration architecture** of Cabinet.

It specifies how Cabinet connects to external and internal services while preserving:

- a frozen orchestration core
- strict layer boundaries
- deterministic execution
- security guarantees
- graceful degradation

This README is **normative** for all integrations.

---

## Cabinet Integration Philosophy

Cabinet is a **control plane**.

Integrations are the “hands” that execute work.
Cabinet only:
- validates and authorizes commands
- schedules and orchestrates tasks
- enforces security and invariants
- observes execution

Cabinet does **not** embed business intelligence about what integrations do.
It routes and controls.

---

## Trust Model (CRITICAL)

All integrations are treated as:

- **untrusted**
- **unreliable**
- **replaceable**
- **security-bound**

No integration is allowed to bypass:
- request authentication
- signature verification
- encryption requirements
- nonce/idempotency rules
- hierarchy/scopes

There are no “trusted internal services”.

---

## Structural Pattern (MANDATORY)

Every integration must follow the same structure:

1. **Application Port**  
   A stable interface owned by the Application layer.

2. **Infrastructure Integration Facade**  
   Coordinates the selection of real vs fallback adapter.

3. **Real Adapter**  
   Performs the actual external call(s).

4. **Fallback Adapter (Fake)**  
   Minimal functional implementation used to preserve pipeline continuity.

This is not optional.
This pattern is the primary mechanism for freezing the core.

---

## Directory Layout

Integrations live under:

app/backend/src/Infrastructure/Integrations

vbnet
Копировать код

Typical structure:

Integrations/<Name>/
<Name>Integration.php # integration facade/coordinator
Real/ # real implementation(s)
Fallback/ # fallback (fake) implementation(s)

vbnet
Копировать код

Shared integration utilities live under:

Integrations/Shared/
Integrations/Registry/

yaml
Копировать код

---

## Real vs Fallback

### Real Adapter

The Real adapter:
- calls the external service
- validates and normalizes responses
- maps external failures into internal error kinds
- is contract-bound and security-bound

### Fallback Adapter (Fake)

Fallbacks are **not mocks**.

A fallback is a **minimal functional capability** that:
- preserves pipeline continuity
- avoids system-wide failure
- enables degraded operation when possible

Fallbacks must never:
- invent privileged behavior
- skip security requirements
- silently hide failures without audit signals

Fallbacks are allowed to:
- return limited fixtures
- return “degraded” results explicitly
- simulate minimal safe behavior

---

## Degradation Rules (CRITICAL)

When an external service fails, Cabinet must:

1. classify the failure deterministically  
2. decide:
   - retry (with policy)
   - degrade (fallback)
   - fail the stage
   - move to DLQ (if applicable)
3. emit observability signals:
   - logs
   - metrics
   - audit events (if security-relevant)

The pipeline must remain consistent even under degradation.

---

## Shared Integration Toolkit

`Integrations/Shared` provides the common building blocks.

Typical responsibilities include:

- HTTP client wrapper (timeouts, retries, headers)
- Circuit breaker
- Health cache and health checks
- Contract validation
- Error mapping into internal failure categories
- Security wrappers:
  - signature
  - nonce generation
  - payload encryption (where required)

All integrations must use the shared toolkit where applicable to keep:
- consistent failure semantics
- consistent security behavior
- consistent observability

---

## Registry & Certificates

Cabinet supports explicit registration of integrations:

- `IntegrationRegistry`
- `IntegrationDescriptorInterface`
- `CertificateRegistry`

This exists to ensure:
- integrations are declared, not discovered implicitly
- certificates and trust materials are centrally managed
- capabilities can be queried deterministically

Integrations must not self-register dynamically at runtime in uncontrolled ways.

---

## Security Requirements

Integrations must comply with the same security model as all other actors:

- explicit authentication / identity binding
- request signatures (canonicalized)
- nonce-based replay protection
- payload encryption (as required)
- key versioning and rotation awareness

Fallbacks do not bypass security.  
Fallbacks execute within the same security posture as the system.

---

## Contracts

Integrations must validate:
- input contracts before sending
- output contracts after receiving

If a contract mismatch occurs:
- treat it as an integration failure
- emit signals
- apply retry/degrade rules
- never pass malformed data into Domain state

Contracts are defined only in:
- `shared/contracts`

No local redefinitions are permitted.

---

## Observability Requirements

Every integration call must be observable:

- structured logs (redacted)
- metrics (success/failure/latency)
- tracing (propagated trace context)
- audit entries when security-relevant

Integrations must not log:
- secrets
- raw encrypted payloads
- private keys
- full sensitive data

---

## Forbidden Practices

The following are forbidden in integrations:

- embedding business rules or workflow decisions
- bypassing Cabinet security requirements
- silent failure without metrics/logs
- ad-hoc HTTP clients (ignoring shared toolkit)
- returning unvalidated external payloads
- implementing “temporary” bypass flags or environment-based weakening

---

## Relationship to Other Documents

This document complements:

- `cabinet/app/backend/src/Infrastructure/README.md`  
  (overall infrastructure responsibilities)

- `cabinet/app/backend/src/Infrastructure/Security/README.md`  
  (security component map)

- `SECURITY-IMPLEMENTATION.md` and `ENCRYPTION-SCHEME.md`  
  (normative security rules and crypto model)

This file defines the **only allowed integration model**.

---

## Final Statement

Integrations are replaceable “tentacles”.
The orchestrator core stays frozen.

If an integration requires changing core orchestration semantics,
the integration design is wrong.

If an integration cannot degrade safely,
the integration is incomplete.

If behavior is unclear:
**deny, log, and escalate**.






