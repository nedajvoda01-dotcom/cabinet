# Security Governance

Defines security governance rules for Cabinet.

## Core Principles

- Networks are hostile
- Integrations are untrusted
- Security is structural, not optional
- Fail-closed behavior mandatory
- No implicit trust anywhere

## Invariants

**Trust requirements:**
- Explicit authentication
- Explicit authorization
- Auditable actions

**Forbidden practices:**
- Disabling security for convenience
- Environment-based weakening
- Undocumented bypasses
- Trusting frontend validation
- Assuming integration correctness

## References

Implementation: `SECURITY-IMPLEMENTATION.md`  
Encryption: `ENCRYPTION-SCHEME.md`  
Hierarchy: `HIERARCHY-GUIDE.md`
