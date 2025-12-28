# cabinet/shared/contracts/README.md — Shared Contracts (Single Source of Truth)

## Location

cabinet/shared/contracts/README.md

---

## Purpose

This document defines the **Shared Contracts subsystem** of Cabinet.

Shared Contracts are the **single source of truth** for all data primitives,
types, and cross-language contracts used by:

- backend (PHP)
- frontend (TypeScript)
- integrations
- security protocols
- tests and parity checks

This README is **normative**.  
Any deviation from these rules is a defect.

---

## Why Shared Contracts Exist

Cabinet is a multi-layer, multi-language system.

Without a strict contract source, systems drift:
- enums diverge
- fields are renamed inconsistently
- error formats mismatch
- security protocols break silently

Shared Contracts exist to:
- eliminate ambiguity
- prevent semantic drift
- enforce determinism across layers
- make breaking changes explicit

---

## What Contracts Are (and Are Not)

Contracts **are**:
- primitive data definitions
- shared schemas
- protocol-level structures
- cross-language invariants

Contracts are **not**:
- business logic
- domain aggregates
- application workflows
- UI models

If something contains logic — it does not belong here.

---

## Directory Structure

shared/contracts/
├─ primitives/ # Human-readable contract definitions (markdown)
├─ implementations/ # Generated code (language-specific)
├─ vectors/ # Test vectors (security, protocol correctness)
└─ README.md

yaml
Копировать код

Each subdirectory has a strict role.

---

## Primitives

Location:
shared/contracts/primitives/

yaml
Копировать код

Primitives define:
- allowed values
- semantic meaning
- invariants
- evolution rules

They are written in **Markdown** intentionally:
- readable by humans
- reviewable by non-developers
- auditable by security teams
- parseable by generators

Primitives are the **authoritative definition**.

---

## Implementations

Location:
shared/contracts/implementations/

yaml
Копировать код

Implementations are:
- generated from primitives
- language-specific
- deterministic

Rules:
- implementations must never be edited manually
- implementations must match primitives exactly
- regenerated code must be committed

If implementation and primitive differ — generation or usage is wrong.

---

## Test Vectors

Location:
shared/contracts/vectors/

yaml
Копировать код

Vectors define **expected behavior** for sensitive logic:
- cryptographic operations
- canonicalization rules
- nonce handling
- signature verification

Vectors are used to:
- test backend implementations
- test frontend implementations
- guarantee protocol parity

If a vector fails — treat it as a security defect.

---

## Workflow Rules

### Changing an Existing Contract

1. Modify the primitive markdown
2. Regenerate implementations
3. Update tests if required
4. Commit all changes together

Partial updates are forbidden.

---

### Adding a New Contract

A new contract may be added only if:
- it is cross-layer
- it has no embedded logic
- it cannot live purely in Domain or Application

Each new primitive must:
- define intent
- define allowed values
- define evolution rules

---

## Usage Rules

Backend code:
- must import contract implementations
- must not redefine enums or constants locally
- must not use string literals where contracts exist

Frontend code:
- must import from generated contracts
- must not redefine types
- must not “mirror” contracts manually

---

## Governance & Approval

Changes to contracts are **high-impact**.

Rules:
- breaking changes require explicit approval
- security-related primitives require security review
- changes must be documented

Contracts are infrastructure, not convenience.

---

## Forbidden Practices

The following are forbidden:

- redefining contracts locally
- duplicating primitives in code
- editing generated implementations
- ignoring parity test failures
- “temporary” contract forks

---

## Relationship to Other Documents

This README must be read together with:

- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`
- frontend generated API README
- backend contract parity tests

Contracts bind the entire system together.

---

## Final Statement

Shared Contracts are the **language of Cabinet**.

If two parts of the system disagree —
the contracts are wrong or ignored.

If a value is unclear —
add it to the primitive, not the code.

If consistency is broken —
**stop and fix contracts first**.
