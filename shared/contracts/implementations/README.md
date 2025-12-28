# Contract Implementations

This directory contains **language-specific implementations**
of the domain contracts defined in `shared/contracts/primitives`.

Implementations are **derived**, not authoritative.

---

## Purpose

The purpose of implementations is to provide:

- executable representations of primitives
- strict semantic parity across languages
- safe reuse in services and clients

They exist to **mirror meaning**, not redefine it.

---

## Source of Truth

The single source of truth is:

shared/contracts/primitives


All implementations must conform exactly to the definitions found there.

If an implementation and a primitive disagree,  
the implementation is wrong.

---

## Supported Languages

Each subdirectory represents one target ecosystem.

Typical examples:
- PHP (backend services)
- TypeScript (frontend, tooling, integrations)

Each language must implement:
- identical field names
- identical constraints
- identical semantics
- equivalent validation behavior

---

## Design Rules

Implementations must:

- be minimal
- avoid business logic
- avoid side effects
- avoid transport concerns

They should contain only:
- data structures
- validation helpers (if needed)
- safe constructors or factories

---

## Parity Guarantees

Parity is enforced by:

- automated tests
- shared test vectors
- contract parity checks
- CI validation

A contract is considered **broken** if parity fails in any language.

---

## Versioning Policy

- primitives define versions implicitly
- implementations follow primitives immediately
- no independent versioning per language

Lag between primitive change and implementation update is unacceptable.

---

## Extensibility

No implementation may:
- add fields
- relax constraints
- reinterpret meaning

Extensions must happen at higher layers.

---

## What This Directory Is NOT

- Not application logic
- Not infrastructure code
- Not serialization schemas
- Not API definitions

It is a strict translation layer.

---

## Failure Modes

Common mistakes to avoid:
- “convenience” fields
- silent defaults
- implicit casting
- language-specific shortcuts

These lead to integration drift.

---

## Outcome

Correct implementations ensure:

- cross-language safety
- predictable integrations
- reliable analytics
- stable security assumptions

If implementations are boring, they are correct.
