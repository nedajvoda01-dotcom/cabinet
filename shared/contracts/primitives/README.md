# Contract Primitives

Authoritative definitions of core domain primitives.

## Purpose

Primitives define **meaning**, not implementation.
Semantic foundation for contracts, APIs, integrations.

## What Is a Primitive

Stable domain concept with:
- Single, unambiguous meaning
- Reused across systems
- Identical interpretation everywhere

Examples: identifiers, statuses, error kinds, trace contexts

## Change Policy

Rules:
- Breaking changes rare and deliberate
- Semantic changes require full audit
- Naming stability mandatory
- Backward compatibility preferred

Primitives change → entire system changes.

## Usage

Primitives generate implementations:
- Backend (PHP)
- Frontend (TypeScript)
- Validated by parity tests

Implementations must follow primitives exactly.
