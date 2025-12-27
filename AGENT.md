# AGENT.md
## Supreme Guardrail for Codex

⚠️ **ABSOLUTE PRIORITY FILE**  
This file has the highest priority.  
Any code generated or modified without complying with this file is considered **INVALID**, even if it compiles and appears to work.

---

## 1. Mandatory Reading Rules (NON-NEGOTIABLE)

### 🔒 Backend Rule
If you are **writing, modifying, or analyzing backend code** (Domain / Application / Pipeline / Integrations):

> **YOU MUST READ `AGENT1.md` FIRST.**

- `AGENT1.md` is the single source of architectural truth for the backend.
- Any decision that violates `AGENT1.md` is an **architectural error**.
- There are **no exceptions**.

---

### 🔒 Frontend Rule
If you are **writing, modifying, or analyzing frontend code** (UI / SPA / BFF / client):

> **YOU MUST READ `AGENT2.md` FIRST.**

- `AGENT2.md` is the single source of architectural truth for the frontend.
- Any UI decision that violates `AGENT2.md` is considered incorrect, even if it is functionally convenient.

---

### 🔒 Full-Stack Rule
If a task involves **both backend and frontend**:

> **READ `AGENT1.md` FIRST, THEN `AGENT2.md`.**

The backend defines meaning and contracts.  
The frontend only reflects and controls them.

---

## 2. What System This Is (DO NOT MISUNDERSTAND)

This system is an **Execution Platform / Conveyor**.

- Backend is a **safe, blind execution conveyor**
- All “intelligence” and “logic” live **outside** the core (executors, parsers, analytics)
- Backend **DOES NOT understand the external world**
- Frontend **DOES NOT understand sources**
- Communication happens only via **normalized contracts and capabilities**

If the code starts to “know” where the data came from, the architecture is broken.

---

## 3. Core Invariants (MERGE-BLOCKING)

### ❌ FORBIDDEN IN BACKEND CORE
- Any vendor names (`autoRu`, `drom`, etc.) above adapters
- Vendor enums, vendor error codes, or vendor-specific branching
- Importing Real adapters into Domain or Application
- “Smart” logic inside pipeline, workers, or core services
- Silent degradation or pretend success
- Missing `traceId`

---

### ❌ FORBIDDEN IN FRONTEND
- `if (source === ...)` or any vendor-based branching
- Direct `fetch` / `axios` usage outside the shared API client
- Interpreting executor-specific semantics
- Guessing or faking success
- Hiding or ignoring `traceId`

---

## 4. Extension Model (ONLY THIS WAY)

### ➕ Adding a New Parser / Source
- Must be added strictly as an **integration plugin**
- Port + Adapter (Real / Fake / Fallback)
- Must return a **normalized result**
- **Must NOT require core changes**

---

### ➕ Adding Analytics
- Analytics is an executor
- Input: normalized data / assets
- Output: an additional normalized block
- UI receives it only via `include / fields`

---

## 5. Fake / Fallback Rule (CRITICAL)

- When an integration is unavailable:
  - **Non-effectful operations** → Fake is allowed
  - **Effectful operations** → Fake is forbidden, return `INTEGRATION_UNAVAILABLE`
- Every fallback decision **must be observable**
- Every error **must include a `traceId`**

---

## 6. Contracts Over Code

- Contracts are more important than implementation
- New fields must be optional
- The meaning of existing fields must never change
- Backend and frontend must speak the **same contract language**

---

## 7. Forbidden Patterns (INSTANT STOP)

If you see or are about to write any of the following — **STOP**:

- “Let’s just check the source here”
- “The UI will handle it”
- “The backend knows how this site works”
- “We’ll refactor this later”
- “This is just an edge case”

Any of these patterns is an **architectural violation**.

---

## 8. Codex Output Requirements

Any generated code **MUST**:
- comply with `AGENT1.md` or `AGENT2.md`
- respect layering and dependency direction
- use normalized contracts
- propagate `traceId`
- introduce no vendor-specific meaning

If compliance is not possible — **Codex MUST STOP and report the issue**.

---

## 9. Final Rule (NEVER FORGET)

> If the system starts to understand the external world —  
> **the architecture is already broken.**

