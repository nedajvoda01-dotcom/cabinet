# cabinet/security/README.md — Security Governance & Non-Runtime Policy

## Location

cabinet/security/README.md

---

## Purpose

This document defines **security governance** for the Cabinet platform.

It covers:
- non-runtime security rules
- organizational and procedural constraints
- security standards that exist outside application code
- how security decisions are governed, approved, and reviewed

This README is **authoritative** for security governance.

---

## Scope of This Document

This document describes:
- **what must be true** about security
- **who is allowed** to change security behavior
- **how security changes are reviewed**
- **where security rules live**

It does **not** describe:
- cryptographic algorithms
- runtime enforcement logic
- HTTP pipeline behavior

Those are defined elsewhere.

---

## Security Layers in Cabinet

Security in Cabinet exists in multiple layers:

1. Governance (this document)
2. Runtime implementation (`Infrastructure/Security`)
3. Execution model (`SECURITY-IMPLEMENTATION.md`)
4. Cryptography (`ENCRYPTION-SCHEME.md`)
5. Hierarchy & authority (`HIERARCHY-GUIDE.md`)

Each layer has a single source of truth.

---

## Governance Principles

### Security Is Structural

Security is not configurable per environment.
Security is not optional.
Security is not feature-flagged.

Any system variant that weakens security is invalid.

---

### Explicit Authority

Security behavior may only be changed by:
- designated maintainers
- approved security reviewers
- formal change processes

No individual developer may weaken security unilaterally.

---

## Change Management

### Allowed Changes

Security changes may include:
- algorithm upgrades
- key size increases
- stricter validation rules
- additional audit signals

All such changes must be:
- documented
- reviewed
- tested
- backward-aware (where applicable)

---

### Forbidden Changes

The following are strictly forbidden:

- disabling security steps
- environment-based weakening (e.g. “dev mode”)
- bypassing encryption or signatures
- trusting internal networks
- trusting frontend enforcement

If a change reduces security posture, it is invalid.

---

## Review & Approval Process

Security-related changes require:

1. Clear problem statement
2. Explicit threat model
3. Documented impact analysis
4. Review by authorized maintainers
5. Audit trail

Emergency changes must still be documented retroactively.

---

## Ownership & Responsibility

Security is owned collectively.

Responsibilities include:
- monitoring for regressions
- responding to incidents
- rotating keys
- reviewing audit logs
- enforcing policy compliance

No single role owns security in isolation.

---

## Audit & Compliance

Security governance requires:
- immutable audit trails
- periodic reviews
- verifiable compliance with defined rules

Audit data must:
- be retained appropriately
- be protected from tampering
- be accessible to authorized reviewers

---

## Documentation Discipline

Security documentation:
- must be accurate
- must be up to date
- must not be ambiguous

Outdated security documents are defects.

---

## Relationship to Other Documents

This README must be read together with:

- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`
- `HIERARCHY-GUIDE.md`
- `AGENT.md`

Together, these documents define the **complete security posture** of Cabinet.

---

## Final Statement

Security governance defines **what is allowed to exist**.

If a behavior is not allowed here —
it must not be implemented.

If security is unclear —
**deny and escalate**.

If enforcement and governance diverge —
**treat it as a critical defect**.
