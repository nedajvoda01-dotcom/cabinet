# ENCRYPTION-SCHEME.md — CABINET CRYPTOGRAPHIC MODEL

This document defines the **mandatory encryption, signing, and key–management scheme**
used by the Cabinet system.

It is written for:
- internal developers
- security reviewers
- AI agents (Codex)
- auditors

Deviation from this scheme is **forbidden** unless explicitly approved.

---

## 1. PURPOSE OF ENCRYPTION IN CABINET

Cabinet is a **secure orchestration layer**.
It routes commands and data between systems it does not own.

Encryption exists to guarantee:
- confidentiality of payloads
- integrity of commands
- authenticity of callers
- replay protection
- forward secrecy where applicable

Cabinet does **not** encrypt “because security”.
It encrypts because it **cannot trust the transport, intermediaries, or integrations**.

---

## 2. THREAT MODEL

Cabinet assumes:

- Network is hostile
- Requests can be replayed
- Payloads can be intercepted
- Integrations can be compromised
- Logs can be inspected
- Traffic can be reordered

Cabinet explicitly protects against:
- replay attacks
- tampering
- impersonation
- downgrade attempts
- leaked credentials
- partial payload disclosure

Cabinet does **not** attempt to protect against:
- compromised client runtime
- compromised Super Admin machine
- malicious approved integration code

---

## 3. HIGH-LEVEL FLOW

Every secured request follows this lifecycle:

1. Client performs **key exchange**
2. Client builds canonical request representation
3. Payload is encrypted
4. Signature is generated
5. Nonce and idempotency key are attached
6. Request is sent
7. Cabinet validates everything **before execution**

Failure at any step **aborts processing immediately**.

---

## 4. KEY TYPES

Cabinet uses **multiple key classes**, each with a strict purpose.

### 4.1 Long-Term Identity Keys

- Asymmetric
- Used to identify a client or integration
- Never used to encrypt payloads directly
- Used only for:
  - authentication
  - key exchange
  - trust establishment

Stored securely.
Rotated via controlled procedures.

---

### 4.2 Session Keys

- Symmetric
- Short-lived
- Generated per session or exchange
- Used for payload encryption

Session keys are:
- derived via key exchange
- bound to identity
- versioned
- disposable

---

### 4.3 Signing Keys

- Used exclusively for request signing
- Never reused for encryption
- Can be asymmetric or symmetric depending on integration trust level

---

## 5. KEY EXCHANGE

Key exchange is mandatory before encrypted communication.

### Properties:
- Explicit
- Versioned
- Audited
- Rotatable

The exchange produces:
- session encryption key
- session signing context
- key identifiers (kid)

No implicit trust is allowed.

---

## 6. PAYLOAD ENCRYPTION

### 6.1 What Is Encrypted

Encrypted:
- request payloads
- sensitive headers (where applicable)
- command parameters

Not encrypted:
- routing metadata
- version identifiers
- non-sensitive protocol fields

---

### 6.2 Encryption Characteristics

Payload encryption must be:
- symmetric
- authenticated (AEAD)
- deterministic only where explicitly required
- resistant to padding oracle attacks

Each payload encryption must:
- include a nonce/iv
- be bound to the session key
- be bound to request context

---

## 7. SIGNATURE MODEL

Encryption alone is insufficient.

Every secured request is **signed**.

### Signature guarantees:
- payload integrity
- request authenticity
- canonical ordering
- tamper detection

Signature verification occurs **before decryption**.

---

## 8. CANONICALIZATION

Before signing:
- request is canonicalized
- fields are ordered deterministically
- whitespace and formatting are normalized

Canonicalization rules are shared across languages via:

