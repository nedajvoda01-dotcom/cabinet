# SECURITY-IMPLEMENTATION.md — CABINET SECURITY EXECUTION MODEL (NORMATIVE)

This document defines **how security is executed and enforced at runtime**
inside the Cabinet system.

This is an **implementation specification**.
Deviation from this behavior is **forbidden**.

---

## 1. SECURITY PHILOSOPHY

Cabinet is a **security-first orchestration system**.

Security is not:
- a feature
- a middleware toggle
- a configuration option

Security is:
- structural
- mandatory
- layered
- deterministic
- fail-closed

No request, command, job, or integration call may bypass security.

---

## 2. SECURITY AS A PRE-EXECUTION PIPELINE

Security in Cabinet is implemented as a **deterministic execution pipeline**
that runs **before any application logic**.

Security MUST execute before:
- controller handling
- command dispatch
- pipeline scheduling
- job execution
- integration calls

If the security pipeline fails, **nothing else runs**.

The order of pipeline steps is **fixed and MUST NOT be reordered**.

---

## 3. SECURITY BOUNDARY

The security boundary is defined as:

> **Everything before the controller layer**

Security ends only when:
- request authenticity is proven
- integrity is verified
- replay protection is enforced
- hierarchy and scopes are validated

After the boundary:
- code may assume a trusted, authenticated, authorized context
- no cryptographic validation is repeated

---

## 4. SECURITY PIPELINE LOCATION (CODE)

Security enforcement exists **only** in the following locations:

- `Http/Security/Pipeline/*`
- `Http/Security/Requirements/*`

The pipeline is assembled exclusively by:
- `SecurityPipelineMiddleware`

No other component may construct or invoke security logic.

---

## 5. ENDPOINT REQUIREMENTS MODEL

### 5.1 Route Requirements

Each HTTP endpoint MUST declare explicit security requirements, including:
- authentication required
- encryption required
- signature required
- nonce required
- required scopes
- required hierarchy level
- rate limits

Requirements are:
- static
- explicit
- version-controlled
- never inferred

### 5.2 Resolution Flow

1. Incoming request is matched to a route
2. Route requirements are resolved
3. Requirements are passed into the security pipeline
4. Each pipeline step enforces its requirement

Missing requirements MUST be treated as denial.

---

## 6. SECURITY PIPELINE STEPS (EXECUTION ORDER)

Each step is **single-purpose** and **fail-fast**.

### 6.1 Authentication Step
- Validates identity
- Resolves actor (user or integration)
- Loads role and scopes
- Rejects unknown or invalid identities

---

### 6.2 Nonce Step
- Validates nonce presence
- Enforces single-use semantics
- Prevents replay
- Records nonce atomically

Nonce reuse is a **security violation**.

---

### 6.3 Signature Step
- Canonicalizes request
- Verifies cryptographic signature
- Confirms key version (kid)
- Confirms identity binding

Signature verification MUST occur **before decryption**.

---

### 6.4 Encryption Step
- Validates encryption metadata
- Decrypts payload
- Confirms session validity
- Rejects malformed ciphertext

Decryption failure aborts execution immediately.

---

### 6.5 Scope Step
- Validates declared scopes
- Matches scopes against endpoint requirements
- Rejects scope overreach

Scopes do not imply hierarchy.

---

### 6.6 Hierarchy Step
- Enforces user hierarchy
- Prevents privilege escalation
- Applies admin/super-admin rules

Hierarchy enforcement is **code-based**, not configuration-based.

---

### 6.7 Rate Limit Step
- Applies per-identity limits
- Applies per-endpoint limits
- Applies per-integration limits

Rate limiting occurs **after authentication**.

---

## 7. SECURITY VS APPLICATION PRECONDITIONS

Security pipeline:
- protects the system boundary
- validates identity, integrity, authenticity

Application Preconditions:
- protect internal consistency
- validate business invariants

Security MUST always run first.
Preconditions MUST NOT replace security checks.

---

## 8. INTEGRATION SECURITY MODEL

Integrations are treated as **untrusted actors**.

Rules:
- integrations authenticate explicitly
- integrations declare scopes
- integrations pass through the same security pipeline
- fallback adapters DO NOT bypass security

Inbound (webhooks) and outbound (calls from Cabinet) interactions
are both subject to security enforcement.

There are no trusted integrations.

---

## 9. AUDIT & TRACEABILITY

Every security-relevant event MUST be auditable:
- authentication failure
- invalid signature
- nonce reuse
- hierarchy violation
- rate-limit violation

Audit records are:
- immutable
- timestamped
- correlated via trace id
- queryable

---

## 10. LOGGING & REDACTION

Security logs:
- are structured
- are redacted
- never include secrets
- never include decrypted payloads

Sensitive data MUST:
- be hashed
- be masked
- be truncated

Logging MUST NOT introduce information leaks.

---

## 11. FAILURE BEHAVIOR

Security failures:
- abort execution
- return deterministic errors
- do not reveal sensitive details
- emit security audit events

System behavior is **fail-closed**.

---

## 12. FORBIDDEN PRACTICES (HARD)

The following are forbidden:

- disabling security steps
- reordering pipeline steps
- conditional security bypasses
- environment-based security weakening
- performing cryptographic validation outside the pipeline
- trusting frontend or integration input

Violations are treated as defects.

---

## FINAL STATEMENT

Security in Cabinet is **runtime infrastructure**, not a feature.

If a request reaches application logic,
it has already passed **every mandatory security gate**.

If security is unclear — deny.
If validation fails — stop.
If behavior deviates — fix immediately.
