# Shared Contracts

This directory defines the **canonical language-agnostic contracts**
used across the entire system.

Contracts describe **meaning**, not behavior.

---

## Role in the System

Contracts form the **semantic backbone** of the platform.

They ensure that:
- backend services
- frontend clients
- background workers
- external integrations

all interpret data **identically**.

If two components disagree on a contract, the system is broken.

---

## Structure Overview

contracts/
├─ primitives/
├─ implementations/
├─ vectors/
└─ README.md


Each subdirectory has a strict responsibility.

---

## Primitives

`primitives/` contains the **authoritative definitions**.

They describe:
- fields
- constraints
- invariants
- semantic meaning

Primitives are:
- language-agnostic
- stable
- reviewed with extreme care

They define **what a thing is**.

---

## Implementations

`implementations/` contains **language-specific realizations**
of primitives.

They must:
- follow primitives exactly
- never extend meaning
- never reinterpret constraints

Implementations define **how a thing is represented** in code.

---

## Vectors

`vectors/` contains **test vectors** used to verify parity.

They provide:
- valid examples
- invalid examples
- edge cases
- cryptographic fixtures

Vectors are used in:
- backend tests
- frontend tests
- integration tests

If a vector fails, parity is broken.

---

## Design Philosophy

Contracts are designed to be:

- explicit
- boring
- strict
- predictable

Ambiguity is treated as a bug.

Convenience is rejected in favor of correctness.

---

## Change Policy

Changes to contracts must be:

1. intentional
2. reviewed
3. backward-aware
4. immediately reflected in implementations

Breaking changes are extremely expensive and avoided.

---

## What Contracts Are NOT

Contracts are not:
- API schemas
- database models
- UI models
- transport formats

They sit **below APIs and above storage**.

---

## Security Implications

Contracts are security-sensitive.

They define:
- trust boundaries
- cryptographic inputs
- identity representations
- authorization contexts

Any drift here creates systemic risk.

---

## Outcome

When contracts are correct:

- integrations are safe
- analytics are reliable
- security assumptions hold
- systems evolve without chaos

Contracts are the quiet foundation everything stands on.
