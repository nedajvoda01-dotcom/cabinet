# Cabinet Frontend

The frontend is a **single, unified interface** for all users of the Cabinet platform.

There are no separate applications for different roles.  
The same interface is rendered for everyone, while **capabilities are progressively restricted** based on access level.

The frontend does not contain business logic and does not orchestrate workflows.  
It is a controlled, observable client of the backend.

---

## Core Concept

- One interface
- One codebase
- One design system
- Multiple access levels

UI capabilities are enabled or disabled purely by permissions and scopes provided by the backend.

---

## Access Model

Users cannot freely register and use the system.

Registration results in:
- a **request for access**
- pending approval by a super admin

Only a super admin can:
- approve registrations
- elevate users to super admin
- see full system-wide visibility

Admins:
- cannot elevate roles
- can invite users
- operate within assigned scopes

Directors and observers:
- have read-only or analytical access
- cannot modify system configuration

All access decisions are enforced by the backend.

---

## Design Principles

- **Desktop-first**
- **No mobile layout**
- **No adaptive UI**
- **Single design target: super admin**

Lower roles receive the same interface with features removed or disabled.

---

## Architecture

The frontend follows a modular structure:

- `app/` — application shell
- `pages/` — routed views
- `features/` — feature-level logic
- `entities/` — domain-aligned UI entities
- `shared/` — reusable infrastructure

---

## API Interaction

The frontend communicates exclusively through:

- a generated API client
- shared contract definitions
- runtime security helpers

No raw HTTP calls are allowed outside the API layer.

---

## Security Model

Frontend security responsibilities are limited to:

- request canonicalization
- signing
- encryption
- nonce handling
- key exchange

All security logic mirrors backend expectations.

Runtime cryptography exists only to satisfy backend security requirements.

---

## Generated API Client

API types and endpoints are generated from shared contracts.

See:
- `src/shared/api/generated/README.md`

The generated client is treated as immutable.

---

## Observability

Frontend emits:

- trace identifiers
- security-related telemetry
- integration status signals

No business metrics are calculated on the client.

---

## What the Frontend Does NOT Do

- No business decisions
- No role interpretation
- No workflow orchestration
- No security policy definition
- No integration logic

---

## Extension Rules

When extending the frontend:

1. Respect access scopes
2. Use generated API types
3. Do not infer permissions
4. Keep UI deterministic
5. Do not add mobile layouts

---

## Status

The frontend is a controlled execution environment.  
It reflects system state but does not own it.
