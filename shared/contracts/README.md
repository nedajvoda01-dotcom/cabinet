# Shared Contracts — Cross-Language Source of Truth

## Location

shared/contracts/README.md

---

## Purpose

The `shared/contracts` module is the **single source of truth** for all
cross-language data definitions used in the Cabinet system.

It exists to:
- eliminate frontend/backend drift
- guarantee type consistency
- provide verifiable security vectors
- enforce deterministic serialization

This directory defines **what data is**, not how it is processed.

---

## Core Principle

> Define once — use everywhere.

Rules:
- a contract is defined exactly once
- all implementations are generated
- no local redefinitions are allowed
- contracts never depend on runtime code

If a value exists here, it must be used everywhere.

---

## High-Level Structure

shared/contracts/
├── primitives/ → Human-readable contract definitions
├── implementations/ → Generated language-specific code
├── vectors/ → Test vectors for verification
├── tools/ → Deterministic generators and utilities
└── README.md → This document

yaml
Копировать код

Each subdirectory has a strict role.

---

## Primitives

The `primitives/` directory contains **authoritative specifications**.

Characteristics:
- written in Markdown
- human-readable
- version-controlled
- reviewed explicitly

Each primitive defines:
- semantic meaning
- allowed values
- invariants
- usage rules

Examples:
- Status
- ErrorKind
- TraceContext
- CapabilitySet
- AssetRef

No implementation logic lives here.

---

## Implementations

The `implementations/` directory contains **generated code only**.

Languages:
- PHP
- TypeScript

Rules:
- files are generated, not written
- manual edits are forbidden
- regeneration must be deterministic
- generated output must be committed

Implementations must match primitives **exactly**.

---

## Vectors

The `vectors/` directory contains **test vectors**.

Purpose:
- verify cryptographic correctness
- verify canonicalization
- verify nonce behavior
- verify signature logic

Vectors are used by:
- backend tests
- frontend tests
- security audits

Vectors are not examples — they are **verification artifacts**.

---

## Modification Process

### Modifying an Existing Contract

1. Edit the primitive (`primitives/*.md`)
2. Regenerate implementations via `php shared/contracts/tools/generate.php`
3. Run parity and vector tests
4. Commit all changes together

### Adding a New Contract

1. Create a new primitive file
2. Define rules and invariants clearly
3. Generate implementations
4. Update usage in backend and frontend
5. Add vectors if security-relevant

### Canonical JSON rule

All contract payloads are canonicalized using **lexicographic key ordering** and
UTF-8 JSON encoding without whitespace prettification. Arrays preserve order;
objects are sorted by key recursively; scalar values are emitted with standard
`json_encode` / `JSON.stringify` semantics. The canonicalizers live in the
generated PHP/TypeScript implementations and must never be edited manually.

---

## Enforcement

The following are forbidden:

- redefining contracts in application code
- hardcoding contract values as strings
- modifying generated implementations
- skipping regeneration steps

Violations are treated as **architecture defects**.

---

## Relationship to Other Layers

- Backend uses generated PHP implementations
- Frontend uses generated TypeScript implementations
- Security relies on vectors
- OpenAPI / HTTP schemas must align with contracts

No other directory may act as a source of truth.

---

## Audience

This document is written for:
- backend developers
- frontend developers
- security engineers
- auditors

---

## Core Responsibilities

`shared/contracts` is the **linguistic foundation** of Cabinet.

It defines:
- how data is named
- how data is shaped
- how data is verified

If two parts of the system disagree —
the contract was violated.
