# Cabinet — Secure Orchestration & Control Plane

Cabinet is an **internal control plane** designed to **safely accept commands, enforce authority, and orchestrate deterministic execution across external systems**.

It is **not** a business application.  
It is **not** a process editor.  
It is **not** a SaaS product.

Cabinet is **infrastructure**.

---

## What Cabinet Is

In simple terms:

> **Cabinet is a high-security command router and execution orchestrator.**

It sits **between humans/automation and machines**, ensuring that:

- only authorized actors can issue commands
- every request is authenticated, signed, and non-replayable
- execution order is deterministic
- retries and failures are handled safely
- nothing runs twice by accident
- every action is observable and auditable

Cabinet **coordinates work** — it does not perform business logic itself.

---

## What Cabinet Is NOT

Cabinet does **not**:

- understand business entities (cars, photos, listings, etc.)
- parse data formats
- call marketplaces directly
- scrape websites
- make business decisions

All such logic lives **outside** Cabinet, behind integrations.

---

## Mental Model

```
Human / Automation
        ↓
 Secure HTTP Boundary
        ↓
     Cabinet Core
        ↓
 Deterministic Pipeline
        ↓
 External Integrations
```

> Cabinet is the **brainstem**, not the brain.

---

## Core Principles

### 1. Frozen Core
The orchestration core is **intentionally conservative**.

- pipeline mechanics
- retries
- DLQ rules
- idempotency
- security enforcement
- hierarchy rules

These are treated as **infrastructure**, not features.

---

### 2. Fail-Closed Security
Security is **structural**, not optional.

- requests pass a deterministic security pipeline
- endpoints without explicit requirements are denied
- no trusted integrations
- no implicit permissions

If security fails, execution **never begins**.

---

### 3. Determinism & Idempotency
Cabinet guarantees:

- deterministic pipeline transitions
- idempotent command handling
- safe retries
- resumable execution
- crash-safe behavior

This enables reliable automation at scale.

---

### 4. Ports & Adapters
Cabinet grows **only through integrations**.

- Application defines ports
- Infrastructure provides adapters
- real adapters are optional
- fallback adapters are mandatory

This allows safe degradation and demo operation.

---

## Architecture Overview

Cabinet is strictly layered:

```
app/backend
├── Domain           # Pure business rules & invariants (NO IO)
├── Application      # Use-cases, commands, orchestration
├── Infrastructure   # DB, queue, crypto, integrations
├── Http             # Routing, controllers, security boundary
├── Bootstrap        # Container & runtime wiring
```

Cross-layer shortcuts are **forbidden**.

---

## Pipeline Execution

Cabinet executes work through a **stage-based pipeline**:

```
Parse → Photos → Publish → Export → Cleanup
```

Each stage is:

- deterministic
- asynchronous
- retry-aware
- DLQ-protected
- observable

Cabinet never understands *what* is being processed — only *that* a stage succeeded or failed.

---

## Integrations (Tentacles)

Each integration consists of:

- Application Port
- Infrastructure Interface
- Real Adapter (optional)
- Fallback Adapter (mandatory)

Fallback adapters ensure the system remains operational even when services are unavailable.

Cabinet ships with a **demo brain**: deterministic fallback integrations that allow the full pipeline to execute without external systems.

---

## Runtime Components

### Persistence
- SQLite (dev/demo)
- idempotent migrations
- transactional repositories
- exact-once semantics via persistence

### Queue & Worker
- persisted job queue
- atomic job claiming
- retry with backoff
- DLQ routing
- standalone worker runtime

### Observability
- structured JSON logging
- persisted audit trail
- metrics as log events
- no silent failures

---

## Frontend (Control Panel)

Cabinet includes a **desktop-only control panel**:

- read-heavy
- contract-driven
- no business logic
- no authority decisions
- reflects backend state only

The UI is an **operator console**, not an application frontend.

---

## Quick Start (Demo Mode)

### Backend
```bash
php -S localhost:8080 -t app/backend/public app/backend/public/index.php
```

### Worker
```bash
php app/backend/bin/worker.php
```

### Frontend
```bash
cd app/frontend
npm install
npm run dev
```

Open: http://localhost:3000

The system runs end-to-end using fallback integrations.

---

## Repository Structure

```
app/
  backend/     # Control plane backend
  frontend/    # Operator UI (desktop-only)
shared/
  contracts/   # Single source of truth for enums & types
docs/
  architecture/
  security/
  pipeline/
  integrations/
```

---

## Use Cases

Cabinet is designed for environments requiring:

- strong security boundaries
- deterministic orchestration
- safe automation
- auditability
- operational confidence

It is **not** intended for rapid prototyping or ad-hoc scripting.

---

## System Components

Cabinet provides:

- Security boundary enforcement
- Pipeline orchestration
- Persistence with idempotency guarantees
- Worker runtime
- Observability and audit trail
- Fallback integration adapters
- Control panel interface

---

## Design Principles

> Infrastructure must be boring.  
> Predictability beats cleverness.  
> Safety beats speed.

Cabinet is intentionally strict — because **infrastructure must be trusted**.
