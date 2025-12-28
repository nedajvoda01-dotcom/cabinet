# cabinet/app/backend/src/Infrastructure/README.md — Infrastructure Responsibilities & Runtime Systems

## Location

cabinet/app/backend/src/Infrastructure/README.md

---

## Purpose

This document defines the **Infrastructure layer** of the Cabinet backend.

It explains:
- what infrastructure is responsible for
- what *must* live here
- what *must not* live here
- how infrastructure supports the Application and Domain layers

This README is **normative** for all infrastructure code.

---

## Role of Infrastructure in Cabinet

Infrastructure is the **execution and side-effect layer**.

It is responsible for:
- persistence
- networking
- cryptography
- external integrations
- background jobs
- observability
- queues and workers

Infrastructure exists to **execute decisions**, not to make them.

---

## What Infrastructure Is NOT

Infrastructure must not:
- contain business rules
- decide workflow logic
- validate domain invariants
- interpret user intent
- bypass security rules

If logic answers *“should this happen?”* — it does not belong here.

---

## Infrastructure Subsystems

Infrastructure is intentionally decomposed into subsystems.

### BackgroundTasks

Location:
