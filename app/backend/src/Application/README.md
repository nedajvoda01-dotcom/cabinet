# Application Layer — Orchestration & Use Case Control

## Location

app/backend/src/Application/README.md

---

## Purpose

The Application layer defines **what the system is allowed to do**.

It is the **orchestration brain** of Cabinet.

This layer:
- receives validated commands
- enforces policies
- coordinates domain objects
- schedules pipeline execution
- calls external integrations via ports

It does **not** implement low-level mechanics
and does **not** contain infrastructure details.

---

## Core Responsibility

The Application layer answers the question:

> “Is this operation allowed, and what should happen next?”

It is responsible for:
- command handling
- query handling
- policy enforcement
- precondition validation
- pipeline coordination
- integration orchestration

---

## High-Level Structure

Application/
├── Commands/ → Write operations (state changes)
├── Queries/ → Read operations (state inspection)
├── Services/ → Application services
├── Pipeline/ → Asynchronous execution orchestration
├── Integrations/ → Ports (interfaces) to external systems
├── Policies/ → Authorization and behavior rules
├── Preconditions/ → Input and state guards
├── Security/ → Security-related service interfaces
└── README.md → This document

yaml
Копировать код

Each submodule has a **single, explicit role**.

---

## Commands

Commands represent **intent to change state**.

Rules:
- Commands are explicit and immutable
- Commands do not return data
- Commands never bypass policies
- Commands never talk directly to infrastructure

Examples:
- CreateTask
- TriggerParse
- RetryJob
- CancelJob

A command answers:
> “I want something to happen.”

---

## Queries

Queries represent **intent to read state**.

Rules:
- Queries never mutate state
- Queries are side-effect free
- Queries operate on read models
- Queries respect access policies

Examples:
- GetTasks
- GetQueueStatus
- GetSecurityEvents

A query answers:
> “Show me what exists.”

---

## Services

Application services:
- coordinate multiple domain objects
- apply policies
- trigger pipelines
- emit events

Rules:
- services are thin
- services are deterministic
- services never embed domain logic
- services never call infrastructure directly

Services bind:
- Commands → Domain → Pipeline

---

## Policies

Policies define **what is allowed**.

Examples:
- access rules
- hierarchy enforcement
- rate and limit rules
- data minimization
- degradation behavior

Rules:
- policies are explicit
- policies are enforced centrally
- policies are testable
- policies are not optional

Policies are **not security** — they are authorization and behavior rules.

---

## Preconditions

Preconditions protect **internal consistency**.

They validate:
- input shape
- payload size
- allowed stage transitions
- asset references
- URLs and identifiers

Rules:
- preconditions run after security
- preconditions fail fast
- preconditions never perform authorization

Preconditions prevent corruption — not attacks.

---

## Pipeline Coordination

The Application layer:
- defines pipeline stages
- schedules jobs
- handles retries and failures
- enforces idempotency

The pipeline is:
- asynchronous
- deterministic
- observable
- resilient

Application decides **what stage comes next**.
Infrastructure decides **how it runs**.

---

## Integrations (Ports)

Application defines **ports**, not adapters.

Ports:
- describe required capabilities
- are technology-agnostic
- define contracts only

Infrastructure provides:
- real adapters
- fallback adapters

Application never knows:
- how HTTP is done
- how storage works
- how encryption is implemented

---

## Security Interfaces

The Application layer declares interfaces for:
- encryption services
- signature services
- nonce services
- key services

This ensures:
- security is enforced consistently
- runtime details remain isolated
- testing is possible

---

## Forbidden Practices

The Application layer MUST NOT:
- talk directly to databases
- use HTTP clients
- access queues directly
- implement cryptography
- infer business meaning

If something depends on “how” — it belongs to Infrastructure.

---

## Audience

This document is written for:
- backend developers
- system designers
- auditors
- AI agents

---

## Summary

The Application layer is the **decision coordinator** of Cabinet.

It decides:
- what is allowed
- what happens next
- how execution is orchestrated

It never executes.
It never stores.
It never assumes.

If something happens — Application explicitly allowed it.
