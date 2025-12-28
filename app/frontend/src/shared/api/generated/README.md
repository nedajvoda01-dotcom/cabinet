# cabinet/app/frontend/src/shared/api/generated/README.md — Generated API Client & Contract Parity

## Location

cabinet/app/frontend/src/shared/api/generated/README.md

---

## Purpose

This document defines the rules and guarantees around the **generated API client**
used by the Cabinet frontend.

It explains:
- why the client is generated
- what guarantees it provides
- how parity with backend contracts is enforced
- what is strictly forbidden to modify

This README is **normative** for API client usage.

---

## Role of the Generated Client

The generated API client is the **only supported way**
for the frontend to communicate with the backend.

Its purpose is to:
- guarantee schema consistency
- enforce request/response typing
- prevent drift between frontend and backend
- surface breaking changes immediately

The client is not a convenience layer.
It is a **safety mechanism**.

---

## Source of Truth

The generated client is derived from:

- backend OpenAPI specification
- shared contracts (`shared/contracts`)
- explicit schema definitions

The frontend **does not define** API shapes.
It consumes them.

If a shape is wrong:
- the backend spec is wrong
- or the contracts are wrong
- or generation failed

The frontend must not “fix” it locally.

---

## Generation Rules

Generated code:
- is produced automatically
- is deterministic
- must be committed to the repository
- must not be edited manually

Any manual change to generated files is forbidden.

If behavior needs to change:
1. Update backend spec or contracts
2. Regenerate client
3. Commit changes together

---

## Parity Guarantees

Parity between frontend and backend is enforced via:

- contract parity tests
- OpenAPI parity tests
- CI validation steps

These tests ensure:
- request fields match exactly
- response fields match exactly
- enum values are aligned
- required/optional fields are consistent

Parity failures are **build blockers**.

---

## Usage Rules

Frontend code must:

- import API calls from the generated client
- not duplicate request logic
- not redefine types
- not hardcode endpoints

All API interaction flows through this layer.

---

## Error Handling

The generated client:
- exposes structured error types
- maps backend error kinds explicitly
- avoids stringly-typed error handling

Frontend code must:
- handle errors by category
- not inspect raw error payloads
- not rely on message strings

---

## Versioning & Updates

When backend API changes:
- the generated client must be regenerated
- old generated artifacts must be removed
- parity tests must be updated if required

Partial updates are forbidden.

---

## Forbidden Practices

The following are strictly forbidden:

- editing generated files manually
- copying generated code into other locations
- bypassing the client with ad-hoc fetch calls
- redefining API types locally
- suppressing parity test failures

---

## Relationship to Other Documents

This README must be read together with:

- `shared/contracts/README.md`
- `app/frontend/README.md`
- backend OpenAPI documentation

This document defines **how API safety is enforced on the frontend**.

---

## Final Statement

The generated API client is a **hard boundary**, not a suggestion.

If the client does not expose what you need —
the backend must change.

If the client exposes something unexpected —
treat it as a defect.

If parity fails —
**stop immediately**.
