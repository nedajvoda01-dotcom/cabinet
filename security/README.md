# Security Governance — Cabinet Security Domain

## Location

security/

---

## Purpose

This directory defines **non-runtime security governance** for the Cabinet system.

It contains:
- security rules
- architectural constraints
- enforcement policies
- review standards
- non-executable specifications

This directory does **not** contain application code.
It defines **what must be true**, not how it is implemented.

---

## Scope

Security governance applies to:

- backend
- frontend
- integrations
- infrastructure
- CI/CD pipelines
- AI agents interacting with the repository

All system components are subject to these rules.

---

## What Lives Here

This directory is the **authoritative source** for:

- security rulesets
- compliance constraints
- forbidden patterns
- architectural security guarantees
- review checklists

Examples:
- mandatory encryption requirements
- role and hierarchy invariants
- audit and logging guarantees
- forbidden bypass scenarios

---

## What Does NOT Live Here

This directory MUST NOT contain:

- runtime security code
- middleware
- encryption implementations
- request validation logic
- authentication handlers

Runtime security belongs exclusively to:

app/backend/src/Infrastructure/Security

yaml
Копировать код

---

## Relationship to Other Security Documents

This directory works together with:

- `SECURITY-IMPLEMENTATION.md`  
  → Defines **how** security is executed at runtime

- `ENCRYPTION-SCHEME.md`  
  → Defines **cryptographic rules and protocols**

- `HIERARCHY-GUIDE.md`  
  → Defines **role and hierarchy invariants**

This directory defines **governance**, not execution.

---

## Enforcement Model

Rules defined here are enforced via:

- architecture tests
- code review
- CI validation
- audit procedures
- agent behavior constraints

Violations are treated as:
- security defects
- release blockers
- architectural regressions

---

## Change Management

Changes to this directory require:

- explicit review
- security approval
- documented rationale

Breaking or weakening rules is forbidden.

If a rule becomes obsolete:
- it must be removed
- replacement must be documented
- ambiguity is not allowed

---

## Audience

This directory is written for:

- security engineers
- system architects
- senior developers
- auditors
- AI agents (Codex)

It is **not** onboarding documentation.

---

## Summary

The `security/` directory defines **what Cabinet must never violate**.

It is not optional.
It is not advisory.
It is not configurable.

If runtime behavior contradicts this directory —  
the runtime behavior is wrong.
