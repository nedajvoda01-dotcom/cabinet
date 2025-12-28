# cabinet/app/frontend/README.md — Frontend Architecture & Access Projection

## Location

cabinet/app/frontend/README.md

---

## Purpose

This document defines the **frontend system of Cabinet**.

It explains:
- the role of the frontend in the overall system
- architectural principles of the UI
- how access control is reflected in the interface
- how frontend security relates to backend security

This README is **normative** for frontend structure and behavior.

---

## Role of the Frontend in Cabinet

The frontend is **not a decision-making component**.

Its role is to:
- present system state
- submit user commands
- reflect permissions and hierarchy
- visualize pipeline execution and results
- provide operational visibility

The frontend does **not**:
- enforce security
- decide permissions
- contain business logic
- bypass backend validation

The backend is always authoritative.

---

## Interface Philosophy

Cabinet has **one interface**.

Principles:
- UI is designed once, for Super Admin
- lower roles see a **reduced projection** of the same UI
- nothing is duplicated per role
- no alternative role-specific layouts exist

Access is reduced by:
- permission filtering
- capability checks
- visibility gating

Not by:
- separate pages
- forks of UI logic
- conditional navigation trees

---

## Role & Capability Model

The frontend:
- receives role and capability data from backend
- never infers permissions locally
- never hardcodes role logic

Capabilities determine:
- which actions are visible
- which controls are enabled
- which data is shown

If a capability is missing:
- the UI hides the control
- the backend would reject it anyway

UI visibility ≠ permission.

---

## Architectural Style

The frontend follows a **feature-oriented structure**:

- `app/` — application bootstrap
- `pages/` — routed screens
- `features/` — user actions and workflows
- `entities/` — domain-shaped UI state
- `shared/` — cross-cutting utilities

This structure exists to:
- prevent coupling
- isolate concerns
- enable gradual extension

---

## Security on the Frontend

The frontend participates in the **security protocol**, but does not define it.

Responsibilities include:
- request canonicalization
- nonce generation
- request signing
- payload encryption
- key exchange

These mechanisms:
- mirror backend expectations
- are validated by parity tests
- must not be modified casually

The frontend never:
- skips security steps
- weakens encryption
- assumes trusted transport

---

## API Interaction

All API interaction goes through:
shared/api/

yaml
Копировать код

Rules:
- generated clients are the source of truth
- manual editing of generated code is forbidden
- API requests must comply with contracts

If API schema changes:
- regenerate clients
- update parity tests
- commit changes together

---

## Desktop-Only Policy

Cabinet frontend is **desktop-only by design**.

Rules:
- no mobile layouts
- no responsive breakpoints
- no touch-first assumptions

This is intentional and enforced.

---

## State & Data Flow

Frontend state:
- mirrors backend read models
- is disposable
- is never authoritative

If frontend state diverges:
- refresh from backend
- do not “fix” locally

---

## Testing Philosophy

Frontend tests focus on:
- contract parity
- security protocol correctness
- permission-based visibility
- deterministic rendering

Visual polish is secondary to correctness.

---

## Forbidden Practices

The following are forbidden:

- implementing business rules in UI
- trusting frontend validation
- role-based branching logic in components
- editing generated API code
- storing secrets in frontend code

---

## Relationship to Other Documents

This README complements:

- `shared/contracts/README.md`
- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`

Frontend security exists to support backend enforcement.

---

## Final Statement

The frontend is a **secure operator console**, not an application brain.

If the UI can do something the backend forbids —  
the UI is wrong.

If the UI hides something the backend allows —  
the UI is incomplete.

If behavior is unclear —  
**ask the backend, not the UI**.
