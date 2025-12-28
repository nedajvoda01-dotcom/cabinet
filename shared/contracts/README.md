# Shared Contracts

This directory defines the **canonical contracts** used across the entire Cabinet platform.

Contracts are the foundational agreement between all participating systems:
- backend
- frontend
- integrations
- external services
- tooling

They describe **what is exchanged**, not **how it is processed**.

---

## Role in the System

Shared contracts act as the **single source of truth** for:

- request and response shapes
- domain primitives
- protocol rules
- cryptographic expectations
- integration boundaries

No component is allowed to diverge from these definitions.

---

## Contract-First Architecture

Cabinet follows a **contract-first** approach.

This means:
1. Contracts are defined first
2. Implementations are generated or written against them
3. Validation and parity are enforced automatically

If a contract changes, all consumers must adapt.

---

## Structure Overview

This directory is structured into four logical parts:

- `primitives/` — human-readable definitions
- `implementations/` — language-specific realizations
- `vectors/` — cryptographic and protocol test vectors
- root-level metadata and documentation

Each layer has a strict responsibility.

---

## Primitives

Primitives describe domain concepts in an **implementation-agnostic** way.

They are:
- written for humans
- reviewed for correctness
- versioned intentionally

They define meaning, not code.

---

## Implementations

Implementations are generated or derived from primitives.

They provide:
- exact type mappings
- serialization rules
- language-specific constraints

Implementations must never introduce semantics not present in primitives.

---

## Vectors

Vectors are used to validate correctness across implementations.

They ensure:
- cryptographic consistency
- signature parity
- encryption compatibility
- nonce behavior alignment

Vectors are authoritative and immutable once published.

---

## Governance Rules

- Contracts are immutable once released
- Breaking changes require explicit versioning
- Silent changes are forbidden
- Consumers must fail fast on mismatch

---

## What This Directory Is NOT

- Not application logic
- Not orchestration logic
- Not UI concerns
- Not infrastructure configuration

It defines **agreements**, nothing else.

---

## Design Principles

- Explicit over implicit
- Deterministic over flexible
- Shared truth over local convenience
- Validation over assumptions

---

## Outcome

If every system follows these contracts:
- integrations remain stable
- refactors are safe
- failures are predictable
- scaling remains controlled

This directory is the backbone of the platform.
