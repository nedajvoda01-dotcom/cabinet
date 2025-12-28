# Contract Primitives

This directory contains the **authoritative human-readable definitions**
of all core domain primitives used in Cabinet.

Primitives define **meaning**, not implementation.

They are the semantic foundation for every contract, API, and integration.

---

## Purpose

Primitives exist to answer one question clearly:

> “What does this concept mean in the system?”

They intentionally avoid:
- programming language concerns
- serialization details
- transport-level specifics

Those belong elsewhere.

---

## What Is a Primitive

A primitive is a **stable domain concept** that:

- has a single, unambiguous meaning
- is reused across multiple systems
- must be interpreted identically everywhere

Examples:
- identifiers
- statuses
- capability sets
- error kinds
- trace contexts

---

## Role in the Architecture

Primitives sit at the **very bottom** of the architecture stack.

Everything else depends on them:
- contracts
- implementations
- validation
- security rules
- analytics
- observability

They must be correct, explicit, and conservative.

---

## Format and Style

Each primitive is documented as a standalone Markdown file.

Every document must clearly define:
- purpose
- structure
- constraints
- invariants
- examples (when relevant)

Ambiguity is not acceptable.

---

## Change Policy

Primitives are extremely sensitive to change.

Rules:
- breaking changes are rare and deliberate
- semantic changes require full review
- naming stability is mandatory
- backward compatibility is preferred

If a primitive changes, the entire system changes with it.

---

## Relationship to Implementations

Primitives are **not code**.

They are used to:
- generate implementations
- review correctness
- validate parity
- onboard new engineers

Implementations must follow primitives exactly.

---

## Validation and Parity

Every primitive must be verifiable through:
- automated tests
- parity checks
- contract validation
- integration vectors

If a primitive cannot be validated, it is incomplete.

---

## What This Directory Is NOT

- Not generated code
- Not API schemas
- Not business logic
- Not UI definitions

It is the semantic layer only.

---

## Design Principles

- Meaning before mechanics
- Stability over flexibility
- Clarity over cleverness
- Explicit constraints over assumptions

---

## Outcome

Well-defined primitives ensure:
- consistent integrations
- predictable security behavior
- reliable orchestration
- safe evolution of the platform

If primitives are correct, the system can scale.
