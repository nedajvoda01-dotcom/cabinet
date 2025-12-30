# MVP Architecture Transformation

## Overview

This document visualizes the architectural transformation from the legacy implementation to the MVP canonical implementation.

---

## Before: Legacy Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    HTTP Request Layer                        │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                  platform/index.php                          │
│              (backwards compat wrapper)                      │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              platform/public/index.php                       │
│                  (main entrypoint)                           │
└─────────────────────────────────────────────────────────────┘
                              ↓
              ┌───────────────┴───────────────┐
              │                               │
        GET requests                   POST /api/invoke
              │                               │
              ↓                               ↓
   ┌──────────────────┐          ┌────────────────────────┐
   │VersionController │          │   require Router.php   │
   │CapabilitiesCtrl  │          │    (legacy code)       │
   └──────────────────┘          └────────────────────────┘
                                              ↓
                              ┌───────────────────────────────┐
                              │      InvokeController         │
                              │  (183 lines, 6 dependencies)  │
                              │                               │
                              │  • Manual auth checks         │
                              │  • Manual policy checks       │
                              │  • Manual limit checks        │
                              │  • Router->getAdapter()       │
                              │  • Router->invoke()           │
                              │  • Manual result filtering    │
                              │  • Manual audit logging       │
                              └───────────────────────────────┘
                                              ↓
                              ┌───────────────────────────────┐
                              │      Legacy Router            │
                              │  • Reads JSON first, then YAML│
                              │  • Hardcoded chain rules      │
                              │  • Direct curl calls          │
                              └───────────────────────────────┘
                                              ↓
                              ┌───────────────────────────────┐
                              │         Adapter               │
                              └───────────────────────────────┘

Registry Files (Dual Format - Sync Risk):
  • adapters.json + adapters.yaml
  • capabilities.json + capabilities.yaml
  • policy.json + policy.yaml
  • ui.json + ui.yaml
  • result_profiles.yaml (YAML only)

Chain Rules: Hardcoded in CapabilityExecutor.php (lines 203-236)
```

---

## After: MVP Canonical Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    HTTP Request Layer                        │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                  platform/index.php                          │
│       (9-line thin wrapper - backwards compat)               │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│              platform/public/index.php                       │
│            (canonical entrypoint - YAML driven)              │
└─────────────────────────────────────────────────────────────┘
                              ↓
              ┌───────────────┴───────────────┐
              │                               │
        GET requests                   POST /api/invoke
              │                               │
              ↓                               ↓
   ┌──────────────────┐          ┌────────────────────────┐
   │VersionController │          │   InvokeController     │
   │CapabilitiesCtrl  │          │   (45 lines - simple!) │
   └──────────────────┘          │                        │
                                 │  • Auth via Executor   │
                                 │  • Single method call  │
                                 └────────────────────────┘
                                              ↓
                              ┌───────────────────────────────┐
                              │    CapabilityExecutor         │
                              │  (Unified Security Pipeline)  │
                              │                               │
                              │  1. Authentication            │
                              │  2. Policy Validation         │
                              │     • UI access check         │
                              │     • Role scopes check       │
                              │     • Chain validation (registry)│
                              │  3. Limits Enforcement        │
                              │     • Rate limiting           │
                              │     • Size checks             │
                              │  4. Routing                   │
                              │     • CapabilityRouter        │
                              │  5. Invoke                    │
                              │     • AdapterClient           │
                              │  6. ResultGate                │
                              │     • Profile-based filtering │
                              │  7. Audit Logging             │
                              └───────────────────────────────┘
                                      ↓           ↓
                          ┌───────────┘           └───────────┐
                          ↓                                   ↓
                ┌──────────────────┐              ┌──────────────────┐
                │CapabilityRouter  │              │  AdapterClient   │
                │ (Registry-based) │              │ (Standardized)   │
                └──────────────────┘              └──────────────────┘
                          ↓                                   ↓
                          └───────────────┬───────────────────┘
                                          ↓
                              ┌───────────────────────────────┐
                              │         Adapter               │
                              └───────────────────────────────┘

Registry Files (YAML Only - Single Source of Truth):
  • adapters.yaml
  • capabilities.yaml (with internal_only + allowed_parents)
  • policy.yaml
  • ui.yaml (with result_profile links)
  • result_profiles.yaml

Chain Rules: Defined in capabilities.yaml, read by CapabilityExecutor
```

---

## Key Differences

| Aspect | Before | After |
|--------|--------|-------|
| **Entrypoint** | Dual (confusing) | Single canonical |
| **Invoke Path** | Legacy Router | Unified CapabilityExecutor |
| **InvokeController** | 183 lines, complex | 45 lines, simple |
| **Registry Format** | JSON + YAML (sync risk) | YAML only |
| **Chain Rules** | Hardcoded in PHP | Registry-driven |
| **Security Pipeline** | Manual, scattered | Unified, consistent |
| **Code Complexity** | High (multiple paths) | Low (single path) |
| **Maintainability** | Requires code changes | Requires data changes |

---

## Data Flow Comparison

### Before: Manual Security Checks
```
Request → InvokeController
  ├─ Manually authenticate
  ├─ Manually check UI access
  ├─ Manually check policy
  ├─ Manually check limits
  ├─ Router->invoke()
  ├─ Manually filter results
  └─ Manually log audit
```

### After: Unified Pipeline
```
Request → InvokeController → CapabilityExecutor
                                   ↓
                        [Unified Security Pipeline]
                                   ├─ Authenticate (automatic)
                                   ├─ Validate Policy (registry-driven)
                                   │  • UI access
                                   │  • Role scopes
                                   │  • Chain rules (from YAML)
                                   ├─ Enforce Limits (automatic)
                                   ├─ Route (via CapabilityRouter)
                                   ├─ Invoke (via AdapterClient)
                                   ├─ Filter (via ResultGate + profiles)
                                   └─ Audit Log (automatic)
```

---

## Configuration Files Evolution

### Before: Dual Format (Sync Risk)
```
registry/
  ├── adapters.json     ← Could drift from YAML
  ├── adapters.yaml
  ├── capabilities.json ← Could drift from YAML
  ├── capabilities.yaml
  ├── policy.json       ← Could drift from YAML
  ├── policy.yaml
  ├── ui.json           ← Could drift from YAML
  ├── ui.yaml
  └── result_profiles.yaml

Chain Rules: Hardcoded in CapabilityExecutor.php
```

### After: YAML Only (Single Source of Truth)
```
registry/
  ├── adapters.yaml
  ├── capabilities.yaml
  │   └── (includes internal_only and allowed_parents)
  ├── policy.yaml
  ├── ui.yaml
  │   └── (includes result_profile links)
  └── result_profiles.yaml

Chain Rules: Defined in capabilities.yaml
```

---

## Benefits Summary

### Developer Experience
- ✅ Single code path (no confusion)
- ✅ 75% less code in InvokeController
- ✅ Add capabilities via YAML (no code changes)
- ✅ Add chain rules via YAML (no code changes)

### Operations
- ✅ Single source of truth (YAML)
- ✅ Automated validation (scripts/validate-registry.sh)
- ✅ Automated verification (scripts/verify-mvp.sh)
- ✅ No JSON/YAML sync issues

### Security
- ✅ Unified security pipeline (consistent)
- ✅ Chain validation at core level
- ✅ Profile-based result filtering
- ✅ Comprehensive audit logging

### Testing
- ✅ 100% test pass rate (28/28 tests)
- ✅ 100% MVP verification (29/29 checks)
- ✅ Unit tests + integration tests
- ✅ Chain tests + profile tests

---

## Files Changed Summary

```
Created (5):
  ✓ platform/src/Adapter/RouterAdapter.php
  ✓ scripts/validate-registry.sh
  ✓ scripts/verify-mvp.sh
  ✓ MVP_SUMMARY.md
  ✓ CANON_GAPS.md

Modified (10):
  ✓ platform/public/index.php
  ✓ platform/src/Http/Controllers/InvokeController.php (183→45 lines)
  ✓ platform/src/Core/CapabilityExecutor.php (registry-driven)
  ✓ platform/src/Registry/RegistryLoader.php (prefer YAML)
  ✓ registry/capabilities.yaml (+internal_only, +allowed_parents)
  ✓ registry/ui.yaml (+result_profile)
  ✓ tests/test-security.php
  ✓ tests/test-capability-chains.php
  ✓ STRUCTURE.tree.txt
  ✓ STRUCTURE.files.txt

Deleted (4):
  ✓ registry/adapters.json
  ✓ registry/capabilities.json
  ✓ registry/policy.json
  ✓ registry/ui.json
```

---

## Verification Commands

```bash
# Verify MVP requirements (29 checks)
./scripts/verify-mvp.sh

# Validate registry consistency
./scripts/validate-registry.sh

# Run all tests
php tests/test-security.php
php tests/integration-test.php
php tests/test-capability-chains.php
php tests/test-result-profiles.php
php tests/test-import-idempotency.php
```

**Result:** ✅ All checks passing, all tests passing, MVP complete!
