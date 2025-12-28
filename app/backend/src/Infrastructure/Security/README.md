# cabinet/app/backend/src/Infrastructure/Security/README.md — Runtime Security Components & Responsibilities

## Location

cabinet/app/backend/src/Infrastructure/Security/README.md

---

## Purpose

This document defines the **runtime security subsystem** of the Cabinet backend.

It explains:
- which security components exist at runtime
- what each component is responsible for
- how cryptographic and security operations are organized
- how security rules defined elsewhere are executed in code

This README is **normative** for runtime security implementation.

---

## Position in the System

Infrastructure/Security is the **execution layer** of security.

It:
- implements cryptographic operations
- enforces protocol guarantees
- manages keys, nonces, and certificates
- supports the HTTP security pipeline

It does **not**:
- define security policy
- define governance rules
- decide who is allowed to change security behavior

Policy lives elsewhere.  
This layer **executes** it.

---

## Security Subsystems Overview

Runtime security is decomposed into explicit subsystems.

---

### AttackProtection

Location:
Infrastructure/Security/AttackProtection

yaml
Копировать код

Responsibilities:
- rate limiting
- basic injection protection
- request-level abuse prevention

This layer:
- reduces blast radius
- provides defensive hardening
- complements, but does not replace, cryptography

---

### Audit

Location:
Infrastructure/Security/Audit

yaml
Копировать код

Responsibilities:
- recording security-relevant events
- sensitive data redaction
- immutable audit trails

Audit events include:
- authentication failures
- nonce reuse
- invalid signatures
- hierarchy violations
- rate limit breaches

Audit must:
- never block execution
- never leak secrets
- always be consistent

---

### Certificates

Location:
Infrastructure/Security/Certificates

yaml
Копировать код

Responsibilities:
- managing integration certificates
- validating presented certificates
- binding certificates to identities

Certificates are:
- explicit
- versioned
- validated before trust is granted

No implicit certificate trust exists.

---

### Encryption

Location:
Infrastructure/Security/Encryption

yaml
Копировать код

Responsibilities:
- symmetric encryption
- asymmetric encryption
- hybrid encryption schemes
- authenticated encryption enforcement

This subsystem:
- encrypts and decrypts payloads
- validates encryption metadata
- binds encryption to session context

Algorithms and schemes must comply with:
- `ENCRYPTION-SCHEME.md`

---

### Identity

Location:
Infrastructure/Security/Identity

yaml
Копировать код

Responsibilities:
- JWT issuing and verification
- session identity resolution
- two-factor authentication support

Identity code:
- resolves *who* the actor is
- does not decide *what* they may do

Authorization happens elsewhere.

---

### Keys

Location:
Infrastructure/Security/Keys

yaml
Копировать код

Responsibilities:
- key storage
- key versioning
- key rotation
- session key exchange

Key management must ensure:
- rotation without downtime
- backward compatibility during transition
- auditability of key usage

Private keys must never:
- be logged
- be serialized
- be exposed outside this subsystem

---

### Nonce

Location:
Infrastructure/Security/Nonce

yaml
Копировать код

Responsibilities:
- nonce validation
- replay protection
- atomic nonce storage
- cleanup of expired nonces

Nonces:
- are single-use
- are time-bound
- are enforced atomically

Nonce reuse is a **hard security failure**.

---

### Signatures

Location:
Infrastructure/Security/Signatures

yaml
Копировать код

Responsibilities:
- request canonicalization
- string-to-sign construction
- signature verification

Signatures:
- are verified before decryption
- bind requests to identities
- guarantee integrity and ordering

Canonicalization rules are shared with:
- frontend runtime
- shared contract vectors

---

### Vault

Location:
Infrastructure/Security/Vault

yaml
Копировать код

Responsibilities:
- secret retrieval
- policy-based secret access
- secure injection of secrets into runtime

Secrets:
- are not stored in configuration files
- are not committed to code
- are accessed via policy-controlled interfaces

---

## Interaction with HTTP Security Pipeline

This subsystem supports:
- `Http/Security/Pipeline/*`

The HTTP layer:
- orchestrates security steps
- decides *which* checks apply

Infrastructure/Security:
- performs the actual cryptographic and validation work

Separation is intentional and enforced.

---

## Dependency Rules

Infrastructure/Security:
- may depend on Domain value objects
- must not depend on Application logic
- must not depend on Http controllers

Security code must be reusable outside HTTP context
(e.g. workers, background jobs).

---

## Failure Behavior

Security failures must:
- fail closed
- be deterministic
- produce auditable events
- never leak sensitive details

Partial success is forbidden.

---

## Forbidden Practices

The following are forbidden:

- implementing security decisions in controllers
- skipping security checks for “internal” calls
- environment-based weakening of crypto
- logging secrets or raw ciphertext
- duplicating cryptographic logic elsewhere

---

## Relationship to Other Documents

This README must be read together with:

- `SECURITY-IMPLEMENTATION.md` — execution model
- `ENCRYPTION-SCHEME.md` — cryptographic rules
- `HIERARCHY-GUIDE.md` — authority and roles

This file defines **how security is executed**, not why.

---

## Final Statement

Runtime security is **infrastructure**, not configuration.

If security logic is unclear — **deny**.  
If crypto behavior diverges — **treat as a defect**.  
If a shortcut is tempting — **it is forbidden**.
