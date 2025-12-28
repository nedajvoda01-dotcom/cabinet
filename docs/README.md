# Documentation Index — Cabinet Platform

## Location

docs/README.md

---

## Purpose

This directory contains **authoritative documentation** for understanding,
operating, and extending the Cabinet system.

Documentation here exists to:
- explain system intent
- describe architectural decisions
- define invariants and constraints
- guide correct usage and extension

It does **not** duplicate code.
It explains **why the code exists in its current form**.

---

## Documentation Philosophy

Documentation in Cabinet follows strict principles:

- Intent over implementation  
- Constraints over examples  
- Invariants over tutorials  
- Clarity over completeness  

If behavior is enforced by code or tests, documentation explains **why**,
not **how**.

---

## Reading Order

For new contributors, the recommended order is:

1. **System Overview**  
   `cabinet/README.md`  
   High-level purpose, philosophy, and boundaries.

2. **Backend Architecture**  
   `app/backend/README.md`  
   How the backend is structured and executed.

3. **Application & Pipeline**  
   `app/backend/src/Application/README.md`  
   `app/backend/src/Application/Pipeline/README.md`  
   Orchestration, execution model, and command handling.

4. **Infrastructure & Integrations**  
   `app/backend/src/Infrastructure/README.md`  
   `app/backend/src/Infrastructure/Integrations/README.md`  

5. **Frontend**  
   `app/frontend/README.md`  
   UI architecture and permission model.

6. **Shared Contracts**  
   `shared/contracts/README.md`  
   Cross-language source of truth.

7. **Security & Governance**  
   `security/README.md`  
   `SECURITY-IMPLEMENTATION.md`  
   `ENCRYPTION-SCHEME.md`  
   `HIERARCHY-GUIDE.md`

---

## Scope of This Directory

This directory contains:
- architectural explanations
- system boundaries
- extension guidelines
- operational understanding

It does not contain:
- API references
- SDK documentation
- code-level comments
- tutorials for end users

---

## Adding New Documents

A new document should be added **only if**:

- the subsystem has non-obvious invariants
- incorrect usage may compromise security or integrity
- the subsystem coordinates multiple layers
- the subsystem is intended for extension

If none apply — prefer code-level documentation.

---

## Source of Truth Rules

There is **no duplicated authority**:

- Contracts → `shared/contracts`
- Runtime security → backend infrastructure
- Governance → `security/`
- Execution model → Application & Pipeline

Documentation must reference the authoritative source,
never redefine it.

---

## Maintenance Rules

Documentation must be:
- versioned
- kept in sync with code
- updated or removed when obsolete

Outdated documentation is considered **technical debt**.

---

## Audience

This directory is written for:
- internal developers
- system operators
- security reviewers
- architects
- AI agents

It is not intended for public users.

---

## Summary

The `docs/` directory explains **how Cabinet should be understood**.

If documentation and code disagree:
- code defines behavior
- documentation must be updated

Ambiguity is not acceptable.
