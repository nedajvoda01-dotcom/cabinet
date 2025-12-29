# Security Governance — Non-Runtime Rules & Standards

## Location

security/README.md

---

## Purpose

This directory defines **security governance rules** for the Cabinet system.

It documents:
- security principles
- non-runtime constraints
- organizational rules
- enforcement expectations

This directory does **not** contain executable code.

It defines **how security must be treated**, not how it is implemented.

---

## Scope

This document applies to:
- backend runtime security
- frontend runtime security
- integrations
- operational processes
- audits

It complements, but does not replace:
- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`
- runtime security code

---

## Security Philosophy

Cabinet is built under the assumption that:

- networks are hostile
- integrations are untrusted
- clients are fallible
- configuration can be wrong
- humans make mistakes

Therefore:
- security is structural
- security is explicit
- security is enforced in code
- security failures are fail-closed

Convenience is never prioritized over security.

---

## Governance vs Runtime Security

| Aspect | Governance | Runtime |
|-----|-----------|--------|
| Purpose | Rules & intent | Enforcement |
| Location | `security/` | `Infrastructure/Security` |
| Executable | ❌ | ✅ |
| Change frequency | Low | Medium |
| Audience | Humans & auditors | System |

Governance defines **what must be true**.  
Runtime ensures **it actually is**.

---

## Key Governance Rules

### No Implicit Trust

- No trusted networks
- No trusted integrations
- No trusted environments
- No trusted UI

Trust must be:
- explicit
- authenticated
- authorized
- auditable

---

### Fail-Closed Behavior

If something is unclear:
- deny
- stop
- log
- audit

Never guess.
Never fallback silently.

---

### Least Privilege

Access must be:
- minimal
- scoped
- revocable
- hierarchical

Visibility is not permission.

---

### Determinism

Security behavior must be:
- deterministic
- reproducible
- testable
- observable

Environment-dependent security is forbidden.

---

## Change Management

Security changes require:
- explicit documentation update
- code audit
- parity test updates (if applicable)
- audit consideration

Undocumented security changes are invalid.

---

## Forbidden Practices

The following are forbidden at the governance level:

- disabling security for development convenience
- environment-based weakening of security
- undocumented bypasses
- trusting frontend validation
- assuming integration correctness

Violations must be treated as incidents.

---

## Relationship to Other Documents

This document:
- defines expectations
- sets boundaries
- provides context

Implementation details live in:
- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`

Hierarchy rules live in:
- `HIERARCHY-GUIDE.md`

---

## Audience

This document is written for:
- security engineers
- system architects
- auditors
- technical leadership

---

## Core Principle

This directory defines **how security is governed** in Cabinet.

Security is not optional.
Security is not configurable.
Security is not negotiable.

If something feels unsafe —
it probably is.
