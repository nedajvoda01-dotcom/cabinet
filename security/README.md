# cabinet/security/README.md — Security Governance & Non-Runtime Rules

## Location

cabinet/security/README.md

---

## Purpose

This document defines **security governance rules** for the Cabinet system.

It describes:
- security principles that exist **outside runtime code**
- organizational and architectural security constraints
- rules that cannot be expressed purely in code
- how security decisions are made and approved

This is **not** an implementation document.  
This is a **governance document**.

---

## Scope

This document governs:

- security standards
- security decision-making
- approval processes
- forbidden practices
- long-term security posture

It applies to:
- system architects
- security engineers
- senior developers
- auditors
- AI agents operating on the repository

---

## Security Domains Separation

Cabinet security is intentionally split into domains:

### 1. Governance Security (this directory)

Defines:
- *what must be true*
- *what is allowed*
- *what is forbidden*
- *who can change security rules*

Lives in:
- `cabinet/security/`

---

### 2. Runtime Security (backend)

Defines:
- *how security is executed*
- *how requests are validated*
- *how cryptography is applied*

Lives in:
- `app/backend/src/Infrastructure/Security/`

---

### 3. Contracts & Protocols

Defines:
- canonical formats
- cryptographic vectors
- cross-language guarantees

Lives in:
- `shared/contracts/`

These domains **must never overlap**.

---

## Governance Responsibilities

Security governance covers areas where code alone is insufficient:

- cryptographic algorithm selection
- key lifecycle policies
- access hierarchy rules
- approval thresholds
- incident response principles
- deprecation policies

Code **implements** governance.
Code does **not define** governance.

---

## Change Authority

Security-related changes require explicit authority.

### Allowed without approval
- documentation clarification
- typo fixes
- formatting changes

### Requires senior review
- changing threat assumptions
- modifying trust boundaries
- changing role semantics
- altering cryptographic primitives

### Requires explicit approval
- weakening security guarantees
- removing security layers
- adding bypasses or exceptions
- redefining trust models

---

## Forbidden Assumptions

Cabinet security **must never assume**:

- trusted network
- honest integrations
- secure client environments
- correct configuration
- correct usage by operators

All assumptions must be explicit and documented.

---

## Security Invariants

The following invariants are **non-negotiable**:

- security is fail-closed
- absence of a rule means denial
- no implicit trust exists
- all actors are authenticated explicitly
- all sensitive operations are auditable

Breaking an invariant is a **security defect**, not a feature.

---

## Relationship to Other Documents

This document is complementary to:

- `SECURITY-IMPLEMENTATION.md`  
  → defines **how** security is enforced in code

- `ENCRYPTION-SCHEME.md`  
  → defines **what cryptography is allowed**

- `HIERARCHY-GUIDE.md`  
  → defines **organizational authority rules**

If conflicts arise:
> Governance documents override implementation details.

---

## Audit & Compliance

Governance security exists to support:

- internal audits
- external reviews
- compliance verification
- long-term maintainability

Every security decision must be:
- explainable
- reviewable
- traceable

---

## Forbidden Practices

The following are strictly forbidden:

- undocumented security behavior
- security changes without documentation updates
- environment-based security weakening
- “temporary” security exceptions
- relying on frontend enforcement

---

## Final Statement

This directory defines **how security decisions are made**,  
not how they are executed.

If runtime security answers *how* —  
governance security answers *why* and *who decided*.

If a security rule is unclear — **deny**.  
If a rule is missing — **escalate**.  
If a rule is violated — **treat as an incident**.
