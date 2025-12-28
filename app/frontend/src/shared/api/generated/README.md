# Generated API Client

This directory contains the **generated API client** used by the Cabinet frontend.

The client is generated from shared contracts and represents the **single source of truth** for frontend-backend communication.

This code is not handwritten and must not be edited manually.

---

## Purpose

The generated client provides:

- strongly typed API requests
- response schemas
- error models
- endpoint definitions
- parity guarantees with backend contracts

It ensures that frontend and backend remain synchronized at the protocol level.

---

## Source of Truth

All generated code originates from:

- `shared/contracts/`
- contract primitives
- protocol definitions
- shared schemas

Any change must start in shared contracts.

---

## Generation Rules

- Generated files are deterministic
- Output must be reproducible
- No manual modifications are allowed
- Formatting and structure are tool-controlled

If regeneration changes behavior, parity must be revalidated.

---

## Parity Verification

This directory includes automated parity checks.

Parity ensures that:
- frontend types match backend expectations
- request and response schemas are aligned
- breaking changes are detected early

Parity failures block integration.

---

## Runtime Usage

The generated client is used by:

- API request layer
- feature-level data access
- entity synchronization

It must not be bypassed or wrapped with custom logic.

---

## What This Directory Is NOT

- Not a place for business logic
- Not a place for adapters
- Not a place for helpers
- Not a place for experiments

Any deviation breaks contract integrity.

---

## Update Workflow

To update the generated client:

1. Modify shared contracts
2. Regenerate implementations
3. Run parity tests
4. Commit generated output

Never patch generated files manually.

---

## Design Guarantees

- Strong typing
- Protocol consistency
- Contract-driven development
- Early failure on mismatch

---

## Status

This directory represents a **compiled view of shared contracts**.  
Treat it as immutable runtime infrastructure.
