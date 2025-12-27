# AGENT2.md
## Frontend Architecture & Engineering Principles (UI for Execution Platform / Conveyor)

**Audience:** Codex + engineers  
**Scope:** frontend (UI) that operates on top of the Execution Platform defined in **AGENT1.md**.  
**Primary goal:** keep the UI *thin, safe, and contract-driven*, while enabling unlimited growth of UI features without leaking “external-world meaning” into the frontend.

**Dogma level:** High.  
Anything marked **MUST / MUST NOT** is **merge‑blocking** if violated.  
Anything marked **SHOULD** is default behavior; deviations require written justification in the PR.

> **Relationship to AGENT1.md**
> - AGENT1 defines the backend as a **frozen core execution conveyor** with **normalized contracts**, **capabilities**, **include/fields**, **traceId**, and **no vendor meaning above adapters**.
> - AGENT2 enforces the **frontend mirror** of those constraints:
>   - UI does not “understand” executors/vendors.
>   - UI consumes only **normalized outcomes** and **capabilities**.
>   - UI never pretends success when backend reports unavailability/degradation.
>   - UI makes all actions observable with **traceId**.

---

## 0. Glossary (frontend)

- **UI / Frontend** — the client application (SPA) that renders screens and triggers backend operations.
- **BFF** — backend-for-frontend (NOT required by default; see §12).
- **Capabilities** — backend-provided feature/permission surface (`/api/capabilities`).
- **TraceId** — correlation identifier propagated across HTTP + WS for observability.
- **Contract** — stable request/response schemas shared between UI and backend (normalized).
- **Entity** — UI-side typed model of a backend resource (Card, Job, User…).
- **Feature** — user-facing action flow (trigger stage, retry job, publish…).
- **Effectful command** — operation that causes real-world effects (publish/delete/send/etc.). Mirrors AGENT1 §5.3.

---

## 1. What this frontend is (and is not)

### 1.1 The frontend is a safe UI for a conveyor
The UI exists to:

- render **read models** returned by the backend
- trigger **commands** exposed by the backend
- subscribe to **events** (WS) and update the UI state
- enforce **client-side safety discipline** (XSS hygiene, token handling, safe URL pre-check, payload limits)
- expose **traceId** to users/operators for debugging and support

The UI does NOT “do the work”. It does not contain executor/vendor logic.

### 1.2 The frontend is NOT a second orchestrator
**Forbidden in the UI (merge-blocking):**

- vendor-specific rules, enums, or branching (“if Avito then…”, “if MarketplaceX then…”)
- interpreting executor response semantics beyond normalized outcome types
- re-implementing backend pipeline behavior (retries, DLQ semantics, idempotency)
- “pretend success” on failures/unavailability
- direct integration calls bypassing backend contracts

**Allowed (and expected):**

- capability-based behavior (“feature enabled”, “permission granted”)
- purely presentational mapping (status → UI pill)
- read model assembly ONLY via backend (include/fields) — not via many ad-hoc client fetches

---

## 2. Frozen rules (frontend invariants)

These invariants are the frontend equivalents of AGENT1 “frozen core”.

### 2.1 Contracts are the single source of truth
- UI MUST use normalized contracts and schemas for core resources (Cards/Jobs/Users/Admin views).
- UI MUST validate core API responses with schemas (e.g. zod) to detect drift early.
- UI MUST NOT rely on “implied” fields or undocumented behavior.

### 2.2 Capabilities-first UI
- UI MUST treat `/api/capabilities` as the single source of truth for:
  - feature availability
  - UI permissions
  - integration presence/availability signals (product view)
- UI MUST NOT hardcode permissions as “truth”. Hardcoded permissions MAY exist only as type definitions and dev fallback, never as final authority.

### 2.3 TraceId everywhere
- UI MUST attach `X-Trace-Id` (or the agreed header) to every backend request.
- UI MUST propagate/associate traceId with WS correlations (use `correlation_id` if provided).
- UI MUST display traceId in user-visible error surfaces (copyable).
- Any user-visible error without traceId is a bug.

### 2.4 No silent success (mirrors AGENT1)
- UI MUST NOT present success for effectful commands unless the backend confirms success.
- If backend responds with `INTEGRATION_UNAVAILABLE` (or equivalent), UI MUST surface it as a failure (with traceId).
- Optimistic UI MUST be limited to safe, non-effectful interactions and MUST roll back on failure.

### 2.5 No direct network calls outside the API client
- Any HTTP request MUST go through `shared/api/client.ts`.
- Direct `fetch/axios` calls outside the client are merge-blocking violations.

---

## 3. Security invariants (frontend)

Frontend security is **hygiene + surface reduction**. Real authorization and SSRF protection remain server-side (AGENT1 §10), but frontend MUST not weaken the system.

### 3.1 XSS discipline
- UI MUST NOT use `dangerouslySetInnerHTML`.
- If HTML rendering is required, it MUST go through `shared/security/sanitize.ts` with an explicit allowlist.
- A test/lint rule MUST enforce the ban.

### 3.2 Token handling
- UI MUST NOT store access tokens in `localStorage`.
- UI SHOULD keep access token in memory and use a refresh mechanism (preferably HttpOnly cookie for refresh if supported).
- UI MUST redact secrets in logs and MUST NOT display raw tokens.

### 3.3 Safe URL & payload hygiene
- Any user-provided URL input MUST be pre-checked client-side via `shared/security/safeUrl.ts`.
- Client-side checks are *not* security boundaries, but reduce accidental abuse and support faster failures.
- UI MUST apply reasonable payload size limits and debounce “spammy” actions (client-side UX protection).

### 3.4 Security headers & CSP (deployment requirement)
- The deployment MUST set CSP and standard security headers.
- The policy MUST be documented and versioned in `shared/security/csp.md`.

---

## 4. Layering and dependency direction (frontend)

Mirrors AGENT1 layering in spirit:

**app/pages (composition)**  
↓  
**features (flows/commands)**  
↓  
**entities (models + schemas)**  
↓  
**shared (platform: api/trace/ws/security/ui)**

### 4.1 Dependency rules (merge-blocking)
- `app/` and `pages/` MUST NOT call API directly.
- `features/` MAY call entities APIs but MUST NOT call raw network APIs.
- `entities/` MAY call `shared/api/client` only.
- `shared/` MUST NOT depend on features/entities.
- No code outside `shared/api/client.ts` may perform network I/O.
- New code MUST NOT import from `_legacy/` (see §10).

---

## 5. API discipline (AGENT1 alignment)

### 5.1 Read paths: include/fields
- UI MUST request minimal read models by default.
- UI SHOULD use `include/fields` explicitly when optional data is required.
- UI MUST validate include/fields inputs client-side (allowlist + limits) before sending.

### 5.2 Effectful commands
- Effectful actions MUST be capability-gated in the UI.
- UI MUST present clear failure states for integration unavailability.
- UI MUST show traceId for any failure.

### 5.3 Integrations status
- UI SHOULD use `/api/integrations/status` for technical troubleshooting screens (admin/operator tooling).
- UI MUST NOT infer integration health from incidental errors alone.

---

## 6. Events (WS) discipline

- WS messages MUST be validated (schema).
- UI SHOULD treat WS as the primary live-update channel for pipeline stage progress.
- UI MUST correlate WS events to user actions using traceId/correlation_id when available.
- UI MUST handle reconnect and backoff safely (no runaway loops).

---

## 7. Design system discipline (tokens-first)

### 7.1 Tokens are the only source of truth for style
- Colors, typography, spacing, radii, shadows MUST come from tokens (`tokens.css`).
- Inline “magic values” for core style primitives are discouraged; deviations require justification.

### 7.2 Component layer
- Shared UI components MUST use tokens and provide consistent accessibility defaults.
- Business/domain meaning MUST NOT be encoded into shared components.

---

## 8. Target folder tree (full, frozen structure)

This is the **target structure**. Changes require an ADR + senior review.

```text
frontend/
└─ src/
   ├─ app/
   │  ├─ App.tsx
   │  ├─ router/
   │  │  ├─ routes.tsx
   │  │  └─ guards.tsx
   │  ├─ layout/
   │  │  ├─ AppShell.tsx
   │  │  ├─ Sidebar.tsx
   │  │  └─ Topbar.tsx
   │  └─ providers/
   │     ├─ AuthProvider.tsx
   │     ├─ CapabilitiesProvider.tsx
   │     ├─ TraceProvider.tsx
   │     ├─ WsProvider.tsx
   │     └─ SecurityProvider.tsx
   │
   ├─ shared/
   │  ├─ api/
   │  │  ├─ client.ts
   │  │  ├─ endpoints.ts
   │  │  ├─ errors.ts
   │  │  ├─ includeFields.ts
   │  │  └─ contracts/
   │  │     ├─ common.ts
   │  │     ├─ capabilities.ts
   │  │     ├─ integrations.ts
   │  │     └─ ws.ts
   │  ├─ auth/
   │  │  ├─ session.ts
   │  │  ├─ tokenStore.ts
   │  │  └─ refresh.ts
   │  ├─ trace/
   │  │  ├─ traceId.ts
   │  │  └─ headers.ts
   │  ├─ ws/
   │  │  ├─ client.ts
   │  │  └─ subscriptions.ts
   │  ├─ security/
   │  │  ├─ csp.md
   │  │  ├─ sanitize.ts
   │  │  ├─ safeUrl.ts
   │  │  ├─ inputLimits.ts
   │  │  ├─ redaction.ts
   │  │  └─ invariants.ts
   │  ├─ rbac/
   │  │  ├─ permission.ts
   │  │  └─ can.ts
   │  └─ ui/
   │     ├─ theme/
   │     │  ├─ tokens.css
   │     │  ├─ typography.css
   │     │  └─ theme.ts
   │     ├─ components/
   │     └─ feedback/
   │        ├─ ErrorBanner.tsx
   │        └─ Toast.tsx
   │
   ├─ entities/
   │  ├─ card/
   │  │  ├─ schemas.ts
   │  │  ├─ api.ts
   │  │  └─ model.ts
   │  ├─ job/
   │  │  ├─ schemas.ts
   │  │  └─ model.ts
   │  └─ user/
   │     ├─ schemas.ts
   │     └─ model.ts
   │
   ├─ features/
   │  ├─ auth/
   │  │  ├─ model/
   │  │  └─ ui/
   │  ├─ pipeline/
   │  │  ├─ model/
   │  │  └─ ui/
   │  ├─ admin/
   │  │  ├─ model/
   │  │  └─ ui/
   │  └─ integrations/
   │     ├─ model/
   │     └─ ui/
   │
   ├─ pages/
   │  ├─ HomePage.tsx
   │  ├─ PipelinePages.tsx
   │  ├─ AdminPages.tsx
   │  └─ ForbiddenPage.tsx
   │
   ├─ tests/
   │  ├─ architecture/
   │  ├─ contracts/
   │  └─ security/
   │
   └─ _legacy/
      ├─ README.md
      └─ ...
```

---

## 9. How to add a new UI feature (uniform extension)

Adding new UI functionality MUST be uniform.

1) Define/extend contracts (if required) in `shared/api/contracts/*` and entity schemas.
2) Add/extend entity API (`entities/<entity>/api.ts`) using `shared/api/client`.
3) Add a feature flow under `features/<feature>/model` (commands, state, WS subscriptions).
4) Compose in `pages/` and wire routes/guards in `app/router`.

**Rules:**
- No direct API calls from pages/app.
- No new network client per feature.
- No permission hardcoding; use capabilities.

---

## 10. _legacy quarantine rules (mandatory)

`_legacy/` is a quarantine and a delete buffer.

- `_legacy/` MUST NOT grow with new features.
- New code MUST NOT import from `_legacy/`.
- Changes in `_legacy/` are allowed only for:
  - security hotfix
  - critical bugfix
  - extraction/migration into new structure
  - deletion

**Enforcement (required):**
- lint rule: ban imports from `_legacy/` outside `_legacy/`
- architecture test: fail CI if new code references `_legacy/`

---

## 11. Tests & enforcement (merge-blocking)

Minimum required enforcement:

1) **No direct fetch**  
   - lint/test: block `fetch/axios` usage outside `shared/api/client.ts`

2) **No legacy imports**  
   - lint/test: block imports from `_legacy/`

3) **No dangerouslySetInnerHTML**  
   - lint/test: block usage (exceptions only via sanitizer module)

4) **TraceId required**  
   - unit test: `client.ts` always attaches traceId
   - UI test: `ErrorBanner` always displays traceId

5) **Schema smoke**  
   - contract tests: core entity responses validate against schemas

---

## 12. BFF policy (default: NO)

A BFF is NOT required by default.

A BFF may be introduced only if:
- server-side rendering/edge auth requires it, or
- UI needs server-side aggregation that the backend will not provide, or
- security/edge controls cannot be achieved with hosting + backend alone.

Any BFF introduction requires an ADR and MUST NOT:
- duplicate business logic
- reintroduce vendor meaning
- fork contracts

---

## 13. Final reminder (non-negotiable)

> If the frontend starts to “understand” the external world,  
> the architecture is already broken.

Frontend is a **safe view and control panel** for the conveyor.  
All meaning and effects are owned by the backend core (AGENT1) and its normalized contracts.
