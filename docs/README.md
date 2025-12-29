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

- `README.md`  
  Internal system overview and run instructions.

---

### 2. Backend Runtime

- `app/backend/README.md`  
  Backend server, worker, database, audit.

---

### 3. Contracts

- `shared/contracts/README.md`  
  Contract authority and regeneration rules.

- `shared/contracts/primitives/README.md`  
  Core domain primitives.

---

### 4. Security & Governance

- `security/README.md`  
  Security governance rules.

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

Core documentation locations:

| Topic | Source |
|----|------|
| System overview | `/README.md` |
| Backend runtime | `/app/backend/README.md` |
| Contracts | `/shared/contracts/` |
| Security governance | `/security/` |
| Architecture | `/docs/` |

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
