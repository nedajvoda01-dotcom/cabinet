# cabinet/docs/README.md — Documentation Index & Governance

## Location

cabinet/docs/README.md

---

## Purpose

This document defines the **documentation system of Cabinet itself**.

It explains:
- how documentation is structured
- how it must be read
- how it evolves
- what is considered authoritative

This file is **not about the product**.  
It is about **how the product is documented**.

---

## Scope

This README governs:
- documentation layout
- documentation responsibilities
- documentation lifecycle
- documentation authority rules

It applies to:
- developers
- security reviewers
- auditors
- AI agents
- maintainers

---

## Documentation Philosophy

Cabinet documentation follows strict principles:

- Documentation explains **intent and invariants**
- Code and tests enforce **behavior**
- Documentation explains **why**, not **how**
- Ambiguity is treated as a defect

If documentation and code diverge:
> **Normative documentation wins**

---

## Documentation Types

Cabinet documentation is split into clearly separated categories.

### 1. Overview Documents

Purpose:
- Explain what a subsystem is
- Define boundaries and non-goals

Examples:
- `cabinet/README.md`
- `app/backend/README.md`
- `app/frontend/README.md`

---

### 2. Implementation Documents (Normative)

Purpose:
- Define mandatory runtime behavior
- Describe security and execution models

Deviation is **forbidden**.

Examples:
- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`
- `HIERARCHY-GUIDE.md`

---

### 3. Layer-Level READMEs

Purpose:
- Explain responsibilities of a specific layer
- Define allowed dependencies
- Protect architectural boundaries

Examples:
- `app/backend/src/README.md`
- `app/backend/src/Infrastructure/README.md`
- `shared/contracts/README.md`

---

### 4. Generated / Derived Documentation

Purpose:
- Explain how generated artifacts are produced
- Define regeneration rules
- Forbid manual modification

Examples:
- `app/frontend/src/shared/api/generated/README.md`

---

## Reading Order

Recommended reading order for new contributors:

1. `cabinet/README.md`  
2. `cabinet/docs/README.md` (this file)  
3. `app/backend/README.md`  
4. `app/backend/src/README.md`  
5. Infrastructure and security documents  
6. Frontend documentation  
7. Shared contracts documentation  

---

## Source of Truth Rules

There is **no duplicated authority**.

- Contracts → `shared/contracts`
- Runtime security → backend infrastructure
- Governance → root-level security documents
- Architecture → layer-level READMEs

If the same rule appears in two places:
> One of them is wrong and must be removed.

---

## When to Add New Documentation

A new document may be added only if **at least one** is true:

- The subsystem has non-obvious invariants
- Incorrect usage may compromise security or integrity
- The subsystem spans multiple layers
- The subsystem is intended to be extended

If none apply:
> Use code comments or tests instead.

---

## Forbidden Documentation Practices

The following are forbidden:

- Duplicating code behavior line-by-line
- Restating contracts outside `shared/contracts`
- Describing security implicitly
- Leaving outdated documentation unmarked
- Keeping “maybe” or “TODO” sections

---

## Maintenance Rules

- Documentation evolves together with the system
- Outdated documents must be updated or deleted
- Documentation debt is treated as technical debt

---

## Final Statement

Documentation is part of Cabinet’s control plane.

If documentation is unclear — **stop**.  
If documentation contradicts behavior — **fix one immediately**.  
If documentation is missing — **treat it as a defect**.
