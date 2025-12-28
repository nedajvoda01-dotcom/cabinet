# ENCRYPTION-SCHEME.md — CABINET CRYPTOGRAPHIC MODEL (NORMATIVE)

This document is **normative** for the Cabinet security protocol:
encryption, signing, key exchange, nonce, and key management.

Deviation is **forbidden** unless explicitly approved.

---

## 0. Scope

This scheme applies to:
- Browser → Backend API requests (frontend client)
- Integration → Backend requests (adapters, webhooks where applicable)
- Backend → Integration requests (signed/encrypted outbound calls)

Out of scope:
- Compromised client runtime
- Compromised Super Admin workstation
- Malicious approved integration code

---

## 1. Goals

Cabinet MUST guarantee:
- Confidentiality of payloads (where required)
- Integrity of requests (always)
- Authenticity of caller identity (always)
- Replay protection (always for secured endpoints)
- Downgrade resistance
- Auditability and provable enforcement

Cabinet MUST fail closed.

---

## 2. Threat Model

Assume:
- Hostile network / MITM
- Replay and reordering
- Partial interception and log inspection
- Compromised integrations (treat as untrusted actors)

Cabinet MUST explicitly protect against:
- replay attacks
- tampering
- impersonation
- downgrade attempts
- leaked credentials
- partial payload disclosure

---

## 3. Protocol Overview (High-level)

Every secured request follows:

1) Key exchange (if no valid session)
2) Canonicalization
3) Payload encryption (if endpoint requires)
4) Signature generation
5) Attach nonce + idempotency key
6) Send request
7) Cabinet validates all steps **before execution**

Any failure MUST abort processing immediately.

---

## 4. Key Classes (Strict Separation)

### 4.1 Long-Term Identity Keys (LTIK)
- Asymmetric
- Used ONLY for: trust establishment and key exchange
- MUST NOT encrypt payloads
- MUST NOT be reused as signing keys (unless explicitly defined in a profile)

### 4.2 Session Keys (SK)
- Symmetric (AEAD encryption key)
- Short-lived
- Derived via key exchange
- Bound to identity and key version (kid)
- Disposable and revocable

### 4.3 Signing Keys (SigK)
- Used ONLY for signing
- MUST NOT be reused for encryption
- Type depends on profile (asymmetric preferred)

---

## 5. Crypto Profiles (Versioned)

Cabinet defines versioned cryptographic profiles.

### 5.1 Profile v1 (CURRENT)
- Encryption: <ALGO, e.g. AES-256-GCM>
- Signature: <ALGO, e.g. Ed25519 / HMAC-SHA256>
- Hash: <ALGO, e.g. SHA-256>
- KDF / Exchange: <ALGO, e.g. ECDH + HKDF>
- Token binding / session binding: <RULES>

### 5.2 Profile vNext (RESERVED)
- (Reserved for rotations/migrations)
- MUST be deployable in dual-accept mode during transition.

---

## 6. Wire Format (NORMATIVE)

All secured requests MUST carry the following protocol fields.

### 6.1 Required headers (secured endpoints)
| Field | Required | Meaning |
|------|----------|---------|
| <TRACE_HEADER> | MUST | Correlation id |
| <NONCE_HEADER> | MUST | Replay protection nonce |
| <IDEMPOTENCY_HEADER> | MUST for commands | Idempotency key |
| <KID_HEADER> | MUST | Key identifier/version |
| <SIG_HEADER> | MUST | Request signature |
| <ENC_HEADER> | MUST if encrypted | Encryption metadata/profile |
| <KEYEX_HEADER> | MAY | Key exchange metadata (when initiating) |

**Note:** Exact names MUST match implementation. No aliases.

### 6.2 Body format
- If encryption required: body MUST be an encrypted envelope (see §7).
- If not encrypted: body MUST still be signed (canonicalization includes body hash).

---

## 7. Encrypted Envelope (NORMATIVE)

When encryption is required, request payload MUST be an envelope:

```json
{
  "v": "<protocol_version>",
  "kid": "<key_id>",
  "alg": "<encryption_alg_profile>",
  "iv": "<base64>",
  "aad": "<base64 or omitted if derived>",
  "ct": "<base64 ciphertext>",
  "tag": "<base64 auth tag>",
  "meta": { "ts": "<optional>", "ctx": "<optional>" }
}
