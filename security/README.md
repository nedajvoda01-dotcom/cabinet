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

