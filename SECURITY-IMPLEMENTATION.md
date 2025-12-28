# SECURITY-IMPLEMENTATION.md — CABINET SECURITY EXECUTION MODEL

This document describes **how security is implemented, enforced, and executed**
inside the Cabinet system.

It is written for:
- internal developers
- security engineers
- auditors
- AI agents (Codex)

This is an **implementation document**, not a guideline.
Deviation from this behavior is forbidden.

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
- fail-closed

No request, command, or integration can bypass security.

---

## 2. SECURITY AS A PIPELINE

Security in Cabinet is implemented as a **deterministic execution pipeline**.

This pipeline executes **before**:
- command handling
- pipeline scheduling
- business orchestration
- integration dispatch

If the security pipeline fails, **nothing else runs**.

---

## 3. SECURITY PIPELINE LOCATION

Security is implemented in:


Specifically:
- `Http/Security/Pipeline`
- `Http/Security/Requirements`

This separation is intentional and enforced.

---

## 4. ENDPOINT REQUIREMENTS MODEL

### 4.1 Route Requirements

Each HTTP endpoint declares **explicit security requirements**.

These include:
- authentication required or not
- encryption required
- signature required
- nonce required
- scopes required
- hierarchy level required
- rate limits

Requirements are:
- static
- explicit
- version-controlled
- not inferred

### 4.2 Resolution Flow

1. Incoming request is matched to route
2. Route requirements are resolved
3. Requirements are passed to the security pipeline
4. Pipeline enforces requirements strictly

No defaults are assumed.
Missing requirements are treated as denial.

---

## 5. SECURITY PIPELINE STEPS

Each step is a **single-purpose, fail-fast unit**.

### 5.1 Authentication Step

- Validates identity
- Resolves actor (user / integration)
- Loads role and scopes
- Fails if identity is unknown

No implicit trust exists.

---

### 5.2 Nonce Step

- Validates nonce presence
- Ensures nonce uniqueness
- Rejects replayed requests
- Records nonce atomically

Nonce reuse is a hard failure.

---

### 5.3 Signature Step

- Canonicalizes request
- Verifies cryptographic signature
- Confirms key version
- Confirms identity binding

Signature is verified **before decryption**.

---

### 5.4 Encryption Step

- Validates encryption metadata
- Decrypts payload
- Confirms session validity
- Rejects malformed ciphertext

Decryption failure aborts execution.

---

### 5.5 Scope Step

- Validates declared scopes
- Matches scopes against endpoint requirements
- Rejects overreach

Scopes do not imply hierarchy.

---

### 5.6 Hierarchy Step

- Validates user hierarchy
- Enforces admin/super-admin boundaries
- Prevents privilege escalation

Hierarchy rules are enforced in code, not config.

---

### 5.7 Rate Limit Step

- Applies per-identity limits
- Applies per-endpoint limits
- Applies per-integration limits

Rate limiting occurs **after authentication**, never before.

---

## 6. PRECONDITIONS VS SECURITY

Preconditions (Application layer) are **not security**.

Differences:
- Security pipeline protects the system boundary
- Preconditions protect internal consistency

Security always runs first.

---

## 7. IDENTITY & ROLE ENFORCEMENT

Roles are resolved during authentication.

Rules:
- Role changes require Super Admin
- Admins cannot escalate roles
- UI visibility does not imply permission

Authorization is validated server-side only.

---

## 8. AUDITING & TRACEABILITY

Every security-relevant event is auditable:

- failed authentication
- invalid signature
- nonce reuse
- hierarchy violation
- rate limit violation

Audit data is:
- immutable
- timestamped
- queryable
- non-destructive

---

## 9. LOGGING & REDACTION

Security logs:
- are structured
- are redacted
- never include secrets
- never include raw payloads

Sensitive data is:
- hashed
- masked
- truncated

Logging failure must not expose secrets.

---

## 10. INTEGRATION SECURITY

Integrations are treated as **untrusted actors**.

Rules:
- integrations authenticate explicitly
- integrations have scoped permissions
- integrations use the same security pipeline
- fallbacks do not bypass security

There are no “trusted” integrations.

---

## 11. FAILURE BEHAVIOR

Security failures:
- return deterministic errors
- do not leak details
- are logged internally
- are auditable

System behavior is **fail-closed**.

---

## 12. TESTING & VERIFICATION

Security is verified via:
- unit tests
- architecture tests
- parity tests
- replay tests
- boundary tests

Security regressions are build blockers.

---

## 13. FORBIDDEN PRACTICES

The following are forbidden:

- disabling security steps
- conditional security bypasses
- environment-based security weakening
- trusting frontend validation
- trusting integration input

---

## FINAL STATEMENT

Security in Cabinet is **structural infrastructure**.

If a request reaches business logic, it has already passed
**every mandatory security gate**.

If security is unclear — deny.
If validation fails — stop.
If behavior deviates — treat as a defect.

This document defines the only valid security execution model for Cabinet.
