Cabinet — Codex Agent Rules (STRICT)
This repository is Cabinet: an internal orchestration system (secure command gateway + pipeline engine + integrations). The agent must operate under strict architectural and security constraints.

0) ABSOLUTE RULE: STRUCTURE IS LAW
You MUST follow the repository structure exactly. You MUST NOT invent new top-level folders, rename existing folders, or relocate modules unless explicitly instructed.

✅ STRUCTURE REFERENCE (PASTE HERE)
Paste the authoritative tree here (from STRUCTURE.txt or your reference doc).
Codex MUST treat it as the single source of truth.

```

```

Enforcement:

If a requested change does not fit the structure above, STOP and propose the closest compliant location.

If you need a new file, create it only inside the allowed directories.

Empty directories must be tracked with .gitkeep (only where applicable).

What Cabinet is (do not redefine) Cabinet is a frozen orchestrator:
It does not “understand business meaning” of payloads.

It securely transports commands and coordinates pipeline stages.

Domain logic stays in external services/integrations. Cabinet coordinates them.

Allowed actions You MAY:
create/edit files strictly within the approved structure,

add documentation, tests, scripts, config consistent with the repo patterns,

generate minimal stubs for ports/adapters/workers/commands,

keep PHP and TypeScript contract parity (shared/contracts).

Forbidden actions You MUST NOT:
weaken the security protocol (nonce/signature/encryption where required),

add public/self-service signup flows (registration is request → super admin approval),

duplicate UI variants per role (one UI, gated by capabilities),

move responsibilities across boundaries (Domain ↔ Application ↔ Infrastructure).

Non-negotiable invariants 4.1 Security-first Requests follow the security pipeline: Auth → Nonce → Signature → Encryption → Scope → Hierarchy → RateLimit (as required per endpoint).
4.2 Pipeline reliability Idempotency keys for state-changing commands

Locks for concurrency

Retry policy + classification

DLQ for terminal failures

4.3 Integrations Each integration must have:

Port in Application/Integrations/*Port.php

Real adapter in Infrastructure/Integrations/*/Real

Fallback adapter/scenario in Infrastructure/Integrations/*/Fallback

Fallback exists to avoid pipeline breakage when external services fail.

4.4 One UI UI is one. Features are hidden/disabled by role/scope/hierarchy/capabilities. UI gating is not security — backend enforces permissions.

Decision process If uncertain:
Find an existing similar pattern in the repo.

Follow naming conventions and folder boundaries.

Ask only if it is critical (e.g., endpoint security requirements).

yaml

Updated rule reminder (so you don’t have to repeat it)
All further .md content I write for you will be English and Codex-ready (explicit, enforceable, not poetic).
If you want, next I can:

Convert your existing HIERARCHY-GUIDE.md, ENCRYPTION-SCHEME.md, SECURITY-IMPLEMENTATION.md into a more Codex-enforceable style (with “MUST/SHOULD/MUST NOT”, checklists, and implementation hooks), OR
Start filling the remaining .md files you have (you listed several at the repo root earlier).
Say which file you want next and I’ll output the full contents in English.
