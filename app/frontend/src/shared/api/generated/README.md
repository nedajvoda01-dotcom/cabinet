# Generated API Client — Parity & Immutability Rules

## Location

app/frontend/src/shared/api/generated/README.md

---

## Purpose

This directory contains the **generated API client** used by the Cabinet frontend.

It exists to guarantee:
- stable request/response typing
- consistent endpoint definitions
- compatibility with backend contracts
- automated detection of drift

This is not handwritten application code.

---

## Source of Truth

The client is generated from the system’s authoritative definitions:

- backend HTTP routes / OpenAPI output (if used)
- shared contracts in `shared/contracts`

This directory is a **consumer** of those sources.
It is not allowed to redefine types or behavior.

---

## Immutability (Critical)

Files in this directory are **generated artifacts**.

Rules:
- DO NOT edit generated files manually
- DO NOT patch generated output directly
- DO NOT “hotfix” types here
- DO NOT add helpers inside generated code

Any manual change here is considered:
- invalid
- non-reviewable
- non-repeatable
- a build integrity defect

All changes must be done at the source, then regenerated.

---

## Parity Guarantee

The frontend must remain compatible with backend expectations.

Parity means:
- endpoint paths match
- methods match
- payload shapes match
- error formats match
- security-required headers match (where enforced)

Drift is detected via parity tests.

---

## Testing

Parity is verified by tests located near this client:

- `openapi-parity.test.ts` (or equivalent)

Rules:
- parity tests are build blockers
- failing parity means the frontend and backend have diverged
- divergence must be resolved by regeneration, not manual edits

---

## Regeneration Process

When backend API or contracts change:

1. Update the authoritative source
2. Regenerate this client
3. Run parity tests
4. Commit the regenerated output together with the source change

The generated output must always be reproducible.

---

## Error Handling Contract

Errors surfaced through the client must preserve:
- backend error classification (`ErrorKind`, etc.)
- structured response shape
- deterministic mapping

The client must not:
- invent error semantics
- convert errors into ambiguous strings
- hide security failures

---

## Security Requirements

This client must support the runtime security protocol by design.

Rules:
- requests must include required protocol headers
- signing / encryption are applied outside generated code
- generated code must remain compatible with canonicalization rules

Security logic belongs to:
- `app/frontend/src/shared/runtime/security`

Generated code must remain clean and protocol-agnostic.

---

## Forbidden Practices

This directory MUST NOT contain:
- business logic
- UI logic
- authorization rules
- security implementation
- hand-written fixes

If code is needed, it belongs outside `generated/`.

---

## Audience

This document is written for:
- frontend developers
- backend developers
- auditors

---

## Core Principle

This directory is a **generated boundary artifact**.

It is immutable by policy.
It is verified by parity tests.
It must always match the system’s authoritative sources.

If it diverges — regenerate, don’t patch.
