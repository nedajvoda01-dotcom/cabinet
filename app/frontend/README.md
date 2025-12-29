# Frontend — Cabinet Control Interface

## Location

app/frontend/README.md

---

## Purpose

The frontend is the **control interface** of the Cabinet system.

It exists to:
- present system state
- issue commands to the backend
- reflect permissions and hierarchy
- display pipeline progress
- surface audit and observability data

The frontend is **not** a business application.
It is a **secured operator console**.

---

## Core Principle

The frontend is a **projection** of backend capabilities.

Rules:
- backend is the single source of truth
- frontend never decides permissions
- frontend never infers security rules
- frontend never bypasses protocol requirements

If the backend disallows an action, the frontend cannot enable it.

---

## Design Philosophy

### Single Interface Model

- There is exactly **one UI**
- It is designed for **Super Admin**
- Lower roles see a **restricted projection**
- No role-specific UIs exist

UI differences are produced by:
- capability filtering
- visibility constraints
- disabled actions

Not by duplication.

---

## Desktop-Only

The frontend is:
- desktop-first
- desktop-only

Rules:
- no mobile layouts
- no responsive breakpoints
- no adaptive UI logic

This is intentional and enforced.

---

## High-Level Structure

app/frontend
├── src/ → Frontend source code
├── tests/ → Frontend tests
└── README.md → This document

yaml
Копировать код

---

## Source Structure (`src/`)

Frontend follows a **feature-oriented structure**:

src/
├── app/ → Application bootstrap
├── pages/ → Route-level pages
├── features/ → User interactions and actions
├── entities/ → Domain representations (UI-only)
├── shared/ → Cross-cutting utilities

yaml
Копировать код

This structure prevents:
- global state sprawl
- cross-feature coupling
- implicit dependencies

---

## Shared Layer

The `shared/` directory includes:
- API client
- runtime security
- access helpers
- UI primitives
- WebSocket client

This is where frontend meets system infrastructure.

---

## Runtime Security

The frontend implements:
- canonical request building
- request signing
- payload encryption
- nonce generation
- key exchange

The frontend **fully participates** in the security protocol.

Rules:
- no plaintext sensitive payloads
- no unsigned requests
- no reused nonces

The browser is treated as an untrusted environment.

---

## API Client

The API client:
- is generated
- matches shared contracts
- is validated via parity tests

Rules:
- generated code is never edited manually
- contracts define request and response shapes
- breaking changes are detected automatically

---

## WebSocket Support

The frontend:
- subscribes to real-time updates
- displays pipeline progress
- reacts to system events

WebSocket data is:
- derived from backend events
- permission-filtered
- read-only

---

## Error Handling

Errors are:
- structured
- classified
- mapped from backend responses

The frontend:
- never invents error meanings
- never suppresses critical failures
- never retries forbidden actions

---

## Forbidden Practices

The frontend MUST NOT:
- store secrets
- bypass backend validation
- reimplement authorization logic
- infer hidden capabilities
- weaken the security protocol

UI visibility does not equal permission.

---

## Audience

This document is written for:
- frontend developers
- system designers
- auditors

---

## Core Responsibilities

The frontend is the **operator console** of Cabinet.

It reflects.
It submits.
It displays.

It never decides.
It never trusts itself.
It never shortcuts security.

If an action is possible — the backend allowed it.
