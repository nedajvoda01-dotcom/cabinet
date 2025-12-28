# Runtime Security

This directory contains the **runtime security implementation** of the Cabinet backend.

Everything here is responsible for enforcing security guarantees **during execution**.  
This is not governance or documentation — this is the code that actively protects the system.

All security mechanisms are explicit, centralized, and testable.

---

## Security Scope

Runtime security covers:

- cryptographic operations
- authentication and identity verification
- request integrity
- replay protection
- key management and rotation
- secret storage
- attack mitigation
- security auditing

No security logic is allowed outside this subsystem.

---

## Core Principles

- **Fail closed**  
  Any security failure results in immediate rejection.

- **No implicit trust**  
  Every request, job, and integration call is verified.

- **Deterministic behavior**  
  Security decisions must be reproducible and auditable.

- **Centralized enforcement**  
  No scattered crypto or ad-hoc validation.

---

## Subsystems Overview

### Encryption

Responsible for:

- symmetric encryption
- asymmetric encryption
- hybrid encryption schemes
- payload protection
- encryption enforcement

Encryption is mandatory where configured by policy.

---

### Signatures

Responsible for:

- canonicalization of data
- string-to-sign construction
- signature verification
- signature auditability

Signatures guarantee integrity and authenticity.

---

### Keys

Responsible for:

- key storage
- key versioning
- key rotation
- session key exchange
- key lifecycle management

Keys are never hard-coded or implicitly trusted.

---

### Nonce

Responsible for replay protection:

- nonce generation
- nonce validation
- atomic nonce storage
- nonce cleanup
- reuse detection

Nonce reuse is treated as a security violation.

---

### Identity

Responsible for:

- token issuance
- token verification
- identity validation
- optional multi-factor authentication

Identity is always verified before access decisions.

---

### Vault

Responsible for secrets management:

- secret retrieval
- secret injection
- policy enforcement
- isolation of sensitive material

Secrets never leak into application logic.

---

### Attack Protection

Responsible for mitigating common attacks:

- rate limiting
- injection protection
- cross-site scripting protection

These protections are enforced uniformly.

---

### Audit

Responsible for security visibility:

- security event recording
- sensitive data redaction
- audit trails

Audit logic never alters execution flow.

---

## Interaction with Other Layers

- Application layer **declares intent**
- HTTP layer **routes and structures requests**
- Infrastructure Security **enforces rules**

Security code does not perform business decisions.

---

## What Is Explicitly Forbidden

- Ad-hoc cryptography outside this directory
- Inline secret handling
- Dynamic security bypasses
- Environment-based logic branches without policy
- Silent degradation of security checks

---

## Documentation

Detailed security behavior is described in:

- `SECURITY-IMPLEMENTATION.md`
- `ENCRYPTION-SCHEME.md`

This README describes **runtime scope only**.

---

## Extension Rules

When extending security:

1. Add explicit interfaces if needed
2. Centralize implementation here
3. Add tests for failure scenarios
4. Add audit events
5. Update documentation

Security changes require review.

---

## Design Guarantees

- Single source of runtime security truth
- Explicit enforcement points
- Replay-safe communication
- Strong cryptographic boundaries
- Full auditability

---

## Status

Runtime security is a critical subsystem.  
Stability and correctness have higher priority than flexibility.
