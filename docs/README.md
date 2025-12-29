# Documentation Index — Cabinet Platform

## Location

docs/README.md

---

## Purpose

This directory contains the **authoritative human-readable documentation**
for the Cabinet system.

Its role is to explain:
- system intent
- architectural boundaries
- operational rules
- extension principles

This documentation does **not** duplicate code.
It explains **why the code exists and how it must be understood**.

---

## Documentation Philosophy

Documentation in Cabinet follows strict rules:

- describes intent, not implementation details
- complements code, never contradicts it
- is explicit about security and invariants
- avoids speculation or informal language

If documentation becomes outdated, it must be:
- updated
- or removed

Ambiguity is forbidden.

---

## Recommended Reading Order

### 1. System Overview

- `cabinet/README.md`  
  High-level explanation of what Cabinet is and what it is not.

---

### 2. Backend Architecture

- `app/backend/README.md`  
  Backend role, runtime responsibilities, and structure.

- `app/backend/src/Application/README.md`  
  Application layer orchestration and use cases.

- `app/backend/src/Application/Pipeline/README.md`  
  Asynchronous pipeline execution model.

---

### 3. Infrastructure & Integrations

- `app/backend/src/Infrastructure/README.md`  
  Runtime infrastructure responsibilities.

- `app/backend/src/Infrastructure/Integrations/README.md`  
  External system adapters and fallback model.

- `app/backend/src/Infrastructure/Security/README.md`  
  Runtime cryptography and enforcement.

---

### 4. Frontend

- `app/frontend/README.md`  
  UI philosophy and runtime security participation.

- `app/frontend/src/shared/api/generated/README.md`  
  Generated API client rules and parity guarantees.

---

### 5. Cross-Language Contracts

- `shared/contracts/README.md`  
  Shared primitives, generated implementations, and vectors.

---

### 6. Security & Governance

- `security/README.md`  
  Security governance and non-runtime rules.

- `SECURITY-IMPLEMENTATION.md`  
  Runtime security execution model.

- `ENCRYPTION-SCHEME.md`  
  Cryptographic design and threat model.

- `HIERARCHY-GUIDE.md`  
  Role hierarchy and access rules.

---

## When to Add Documentation

Add a new document **only if** at least one condition is met:

- the subsystem has non-obvious invariants
- incorrect usage could compromise security or data integrity
- the subsystem is intended to be extended
- the subsystem coordinates multiple layers

If none apply, prefer code-level documentation.

---

## Single Source of Truth

There is no duplicated authority:

| Topic | Source |
|----|------|
| Contracts | `shared/contracts` |
| Runtime Security | `Infrastructure/Security` |
| Governance | `security/` |
| Architecture | this directory |

If two documents disagree — the system is inconsistent.

---

## Audience

This documentation is written for:
- engineers
- security auditors
- auditors
- technical leadership

---

## Purpose

This directory is the **map of the system**.

If you are lost — start here.
If something is unclear — documentation must be improved.
If documentation and code disagree — fix the documentation or the code.

Never ignore the mismatch.
