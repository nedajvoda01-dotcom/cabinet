# Documentation Index

This directory contains structured documentation for the Cabinet platform.

The goal of this documentation is not to duplicate the code, but to explain **how the system is intended to be understood, extended, and operated**. Every document here has a clear scope and a defined audience.

If you are new to the project, follow the reading order below.

---

## Recommended Reading Order

### 1. Project Overview
- **Root overview**
  - `cabinet/README.md`  
    High-level description of the system, its purpose, and core principles.

### 2. Backend Architecture
- **Backend entry point**
  - `app/backend/README.md`  
    How the backend is structured, how to run it, and how responsibilities are divided.
- **Application layer**
  - `app/backend/src/Application/README.md`  
    Commands, queries, policies, preconditions, and orchestration logic.
- **Pipeline orchestration**
  - `app/backend/src/Application/Pipeline/README.md`  
    Asynchronous processing, jobs, retries, workers, and state transitions.

### 3. Infrastructure and Integrations
- **Infrastructure overview**
  - `app/backend/src/Infrastructure/README.md`  
    Persistence, queues, observability, background tasks.
- **Integrations**
  - `app/backend/src/Infrastructure/Integrations/README.md`  
    How external services are connected, including real and fallback adapters.
- **Runtime security**
  - `app/backend/src/Infrastructure/Security/README.md`  
    Cryptography, keys, nonces, vaults, and security enforcement.

### 4. Frontend
- **Frontend overview**
  - `app/frontend/README.md`  
    UI architecture, role-based access, and runtime security on the client.
- **Generated API client**
  - `app/frontend/src/shared/api/generated/README.md`  
    API client generation and contract parity guarantees.

### 5. Contracts (Cross-Language)
- **Source of truth**
  - `shared/contracts/README.md`  
    Shared contracts used across backend and frontend, including primitives and vectors.

### 6. Security and Governance
- **Security governance**
  - `security/README.md`  
    Non-runtime security policies and standards.
- **Implementation details**
  - `SECURITY-IMPLEMENTATION.md`
  - `ENCRYPTION-SCHEME.md`
  - `HIERARCHY-GUIDE.md`

---

## Documentation Principles

- Documentation describes **intent**, not implementation details.
- If something is enforced by tests or code, documentation explains *why*, not *how*.
- Security-related behavior is always documented explicitly.
- There is no duplicated source of truth:
  - Contracts live only in `shared/contracts`
  - Runtime security lives only in backend infrastructure
  - Governance lives only in root `security/`

---

## When to Add New Documents

Add a new document only if at least one of the following is true:

- The subsystem has non-obvious invariants.
- Incorrect usage may compromise security or data integrity.
- The subsystem is expected to be extended by other developers.
- The subsystem coordinates multiple layers (Application + Infrastructure).

If none of the above apply, prefer code-level documentation.

---

## Status

This documentation set evolves together with the system.  
Outdated documents must be updated or removed — never left ambiguous.
