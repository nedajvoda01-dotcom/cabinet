# HIERARCHY-GUIDE.md — CABINET ACCESS HIERARCHY & AUTHORIZATION MODEL (NORMATIVE)

This document defines the **hierarchical access model** of the Cabinet system.

It is **normative** and applies to:

- Backend authorization
- Frontend visibility filtering
- API access control
- Pipeline and administrative operations
- AI agents (Codex)
- Auditors and security reviewers

Deviation from this guide is **forbidden** unless explicitly approved.

---

## 0. Purpose

Cabinet is an **internal control plane**.

Hierarchy exists to:

- prevent privilege escalation
- enforce separation of authority
- limit blast radius of mistakes
- provide auditable control over access

Hierarchy is **not** a UI feature.  
Hierarchy is a **security boundary**.

---

## 1. Core Principles (Absolute)

### 1.1 Authorization is server-side only
UI visibility does not grant permission.

### 1.2 Hierarchy is enforced before execution
No command, pipeline stage, or admin action may run without hierarchy validation.

### 1.3 Hierarchy is monotonic
Lower roles cannot act on behalf of higher roles.

### 1.4 Hierarchy is explicit
There are no implicit privileges or “convenience” escalations.

### 1.5 Fail closed
If hierarchy cannot be resolved → deny.

---

## 2. Role Model

Cabinet defines a strict role hierarchy.

### 2.1 Roles (Ordered)

From lowest to highest:

1. **User**
2. **Admin**
3. **Super Admin**

This ordering is absolute and non-negotiable.

### 2.2 Hierarchy Level Representation (Normative)

Each role is mapped to a strictly ordered **hierarchy level**.

Example mapping (conceptual):

- User → level `10`
- Admin → level `20`
- Super Admin → level `30`

Rules:

- Higher level always dominates lower level.
- Comparison must be deterministic and total.
- Equal levels do **not** imply authority over peers unless explicitly allowed by policy.
- Level must be derived from authoritative server-side identity, never from the client.

---

## 3. Role Semantics

### 3.1 User

A User:

- can authenticate **only after approval**
- can execute permitted commands within assigned scopes
- can view only authorized data
- cannot manage other users
- cannot modify system configuration
- cannot perform administrative pipeline control beyond granted permissions

A User is an operator, not an authority.

---

### 3.2 Admin

An Admin:

- can invite users
- can manage users **below Admin level**
- can observe and operate pipelines within allowed scopes
- can perform limited administrative actions (retry, cancel, inspect) where permitted

An Admin:

- **CANNOT** promote users to Admin or Super Admin
- **CANNOT** modify hierarchy rules
- **CANNOT** bypass security or pipeline enforcement

Admin authority is delegated, not sovereign.

---

### 3.3 Super Admin

A Super Admin:

- approves or rejects access requests
- assigns, promotes, demotes, or revokes any role
- has full visibility into system state (subject to policy-defined minimization)
- can perform all administrative and security operations

Important:

- Super Admin is **not** a special code path.
- Super Admin differs only by permissions and visibility, not by execution logic.

Super Admin actions are high-risk and MUST be auditable.

---

## 4. Registration & Approval Flow

### 4.1 Registration

Registration does **NOT** create an active account.  
Registration creates a **pending access request**.

Pending users have:

- no execution rights
- no data access
- no pipeline interaction

### 4.2 Approval

Only Super Admin may approve access requests.

Approval assigns:

- role
- scopes
- hierarchy level (derived from role)

Without approval → access is denied.

---

## 5. Promotion, Demotion, Revocation (Hard Rules)

### 5.1 Promotion

- Only Super Admin may promote users.
- Admins may NOT promote anyone.
- Self-promotion is forbidden.
- Circular promotion paths are forbidden.

### 5.2 Demotion & Revocation

- Only Super Admin may demote or revoke Admins.
- Super Admin accounts may be revoked only by another Super Admin.

Revocation MUST:

- invalidate sessions
- invalidate active keys (where applicable)
- be recorded in audit logs

---

## 6. Scope vs Hierarchy (Separation of Concerns)

Hierarchy answers: **“WHO can act on WHOM”**  
Scope answers: **“WHERE they can act”**

Rules:

- Hierarchy violations override scope allowances.
- Scope does NOT grant authority over higher hierarchy levels.
- Both checks MUST pass for any privileged operation.

Example:

An Admin with broad scope still cannot promote another Admin.

### 6.1 Acting On Behalf Of (Formal Definition)

An actor is considered to be **acting on behalf of** another identity if it does any of:

- modifies that identity’s role, scope, or permissions
- performs privileged operations attributed to that identity
- impersonates, delegates, or proxies authority
- triggers irreversible admin operations affecting that identity’s authority

Rules:

- Acting on behalf of a higher hierarchy level is forbidden.
- Acting on behalf of a peer requires explicit, auditable delegation (if delegation exists).
- Integrations and automation are treated as actors with hierarchy and scopes.

---

## 7. Enforcement Points (Mandatory)

Hierarchy MUST be enforced at:

### 7.1 HTTP Security Pipeline

- via `HierarchyStep`
- before request reaches controllers

### 7.2 Application Layer

- via `HierarchyPolicy`
- before command execution

### 7.3 Administrative Operations

Including but not limited to:

- user management
- role assignment
- pipeline control

Bypassing any enforcement point is forbidden.

### 7.4 Canonical Enforcement Mapping (Normative)

Hierarchy enforcement is implemented only in:

- `app/backend/src/Http/Security/Pipeline/HierarchyStep.php`
- `app/backend/src/Application/Policies/HierarchyPolicy.php`

Ad-hoc role checks outside these components are forbidden unless explicitly approved.

---

## 8. UI Visibility Rules

There is **ONE interface**.

- UI is designed for Super Admin.
- Lower roles see a reduced projection of the same interface.

Rules:

- UI hiding does NOT grant security.
- Backend MUST enforce permissions regardless of UI state.
- UI may never assume role authority.
- UI may never “optimistically” enable restricted operations.

---

## 9. Audit & Accountability

All hierarchy-affecting actions MUST:

- emit security audit events
- record actor identity
- record target identity
- record previous and new roles/scopes
- record timestamp and trace id

Hierarchy violations MUST be logged as security incidents.

### 9.1 Audit Severity Levels (Recommended)

Hierarchy-related audit events should be classified as:

- **INFO**
  - successful authorized hierarchy actions
- **WARNING**
  - rejected peer-level operations
  - rejected scope + hierarchy conflicts
- **CRITICAL**
  - escalation attempts
  - acting-on-behalf violations
  - Super Admin misuse patterns

CRITICAL events SHOULD trigger alerts.

---

## 10. Forbidden Patterns

The following are explicitly forbidden:

- role-based UI duplication (separate screens per role)
- “temporary” escalations
- implicit admin privileges
- bypassing hierarchy for automation
- trusting frontend role claims
- hardcoding role checks outside policy layer
- allowing “peer control” unless explicitly designed and audited

---

## 11. Extension Rules

If new roles are ever introduced:

- They MUST fit into a single linear hierarchy.
- They MUST NOT overlap authority ambiguously.
- They MUST NOT bypass existing enforcement logic.
- Migration MUST be explicit and auditable.
- Documentation and tests MUST be updated in the same change.

---

## Final Statement

Hierarchy in Cabinet is a **security primitive**, not a convenience.

If a request:

- violates hierarchy
- attempts escalation
- or cannot be clearly authorized

→ it MUST be denied.

When in doubt: **deny and audit**.
