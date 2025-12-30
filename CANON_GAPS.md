# Canon Gaps Analysis

## Entry Points

### Current State
1. **platform/index.php** - Thin wrapper that delegates to platform/public/index.php (Lines 1-10)
2. **platform/public/index.php** - Main entry point with routing logic (Lines 1-216)

### Issue
- Two files exist, although platform/index.php is already a thin wrapper
- This is acceptable for backward compatibility

### Fix Priority: LOW (already mostly canonical)

---

## Legacy Router Usage

### Current State
**platform/public/index.php (Lines 136-149)**:
```php
if ($method === 'POST' && $path === '/api/invoke') {
    // Phase 2: Invoke endpoint (refactored)
    // For now, use the existing Router class for backward compatibility
    // Phase 3/4 integration can be done more cleanly later
    require_once __DIR__ . '/../Router.php';
    $legacyRouter = new Router(
        $registryPath . '/adapters.yaml',
        $registryPath . '/capabilities.yaml'
    );
    
    $controller = new InvokeController(
        $legacyRouter,
        $policy,
        $limits,
        $resultGate,
        $storage,
        $uiConfig
    );
```

**InvokeController (Lines 12-29)**:
- Takes $router as dependency (the legacy Router class)
- Calls $router->getAdapterForCapability() and $router->invoke()

### Issue
- POST /api/invoke does NOT go through CapabilityExecutor
- Uses legacy Router.php directly
- CapabilityExecutor exists but is not used by InvokeController

### Fix Priority: **CRITICAL** (breaks MVP requirement #2)

---

## Registry: JSON vs YAML

### Current State
Both JSON and YAML files exist in registry/:
- adapters.json (687 bytes) + adapters.yaml (670 bytes)
- capabilities.json (5530 bytes) + capabilities.yaml (4907 bytes)
- policy.json (1613 bytes) + policy.yaml (1821 bytes)
- ui.json (1109 bytes) + ui.yaml (1025 bytes)
- result_profiles.yaml (2501 bytes) - YAML only

### Files Reading Registry
**Router.php (Lines 16-35)**:
- Tries JSON first, then YAML
- Uses both formats

**RegistryLoader** (needs investigation):
- Likely reads YAML as primary

### Issue
- Dual format creates sync risk
- Router prefers JSON over YAML

### Fix Priority: **HIGH** (MVP requirement #3)

---

## Hardcoded Chain Rules

### Current State
**CapabilityExecutor.php (Lines 203-236)**:

```php
private function isInternalOnlyCapability(string $capability): bool {
    // Internal capabilities that should not be called directly from UI
    $internalCapabilities = [
        'storage.listings.upsert_batch',
        'storage.imports.register',
        'storage.imports.mark_done',
        'parser.calculate_hash',
        'parser.parse_csv',
    ];
    
    return in_array($capability, $internalCapabilities);
}

private function isAllowedChain(string $parentCapability, string $childCapability): bool {
    // Define allowed capability chains
    $allowedChains = [
        'import.run' => [
            'parser.calculate_hash',
            'parser.parse_csv',
            'storage.imports.register',
            'storage.listings.upsert_batch',
            'storage.imports.mark_done',
        ],
    ];
    
    if (!isset($allowedChains[$parentCapability])) {
        return false;
    }
    
    return in_array($childCapability, $allowedChains[$parentCapability]);
}
```

### Issue
- Chain rules are hardcoded in PHP
- Not in registry data
- Adding new chains requires code change

### Fix Priority: **CRITICAL** (MVP requirement #4)

---

## Result Profiles and UI Integration

### Current State
**result_profiles.yaml** exists with:
- profiles: internal_ui, public_ui, ops_ui
- ui_profiles mapping: admin → internal_ui, public → public_ui, etc.

**ResultGate.php**:
- Has filter() method that takes $ui parameter
- Needs investigation if it reads result_profiles.yaml

**ui.yaml**:
- Does NOT explicitly list result_profile per UI
- Only has allowed_capabilities and scopes

### Issue
- ui.yaml should contain result_profile link
- ResultGate may not be reading from result_profiles.yaml properly

### Fix Priority: **HIGH** (MVP requirement #5)

---

## Summary: Fix Order

1. **✅ CRITICAL: Remove legacy Router from invoke path**
   - Update InvokeController to use CapabilityExecutor ✓
   - Update platform/public/index.php to instantiate CapabilityExecutor ✓
   - Remove Router.php usage from invoke endpoint ✓

2. **✅ CRITICAL: Move chain rules to registry**
   - Add internal_only and allowed_parents to capabilities.yaml ✓
   - Update CapabilityExecutor to read from registry ✓
   - Remove hardcoded arrays ✓

3. **✅ HIGH: YAML as source of truth**
   - Remove JSON files or make them generated ✓
   - Update Router.php (if still needed) to read YAML only ✓
   - Create validation script ✓

4. **✅ HIGH: Result profiles in ui.yaml**
   - Add result_profile field to each UI in ui.yaml ✓
   - Ensure ResultGate reads from result_profiles.yaml ✓
   - Verify end-to-end filtering ✓

5. **✅ LOW: Verify single entrypoint**
   - Already done (platform/index.php is thin wrapper) ✓
   - Just verify and document ✓

---

## Additional MVP Requirements Completed

6. **✅ Network Isolation (Step 6.1 & 6.2)**
   - Docker networks configured (edge + mesh) ✓
   - Adapters isolated in mesh network ✓
   - Test script created: test-network-isolation.sh ✓
   - Documented as merge-blocker ✓

7. **✅ Key MVP Scenarios (Step 7)**
   - GET /api/capabilities returns filtered list ✓
   - Catalog search capabilities implemented ✓
   - Import orchestration through core ✓
   - Idempotency enforced ✓

8. **✅ Developer Ergonomics (Step 8)**
   - Created scripts/new-adapter.sh ✓
   - Created scripts/new-capability.sh ✓
   - Created scripts/run-smoke.sh ✓
   - Created scripts/check-architecture.sh (grep checks) ✓
   - Created scripts/ci-verify.sh (all merge-blockers) ✓

---

## Status: 100% MVP COMPLETE ✓

All CANON_GAPS have been addressed and verified. The platform now has:
- ✓ Single canonical code path for all invocations
- ✓ Registry-driven configuration (YAML only)
- ✓ Chain rules in data (not code)
- ✓ Result profiles applied
- ✓ Network isolation enforced
- ✓ Developer-friendly tooling
- ✓ Comprehensive test coverage
- ✓ All merge-blocker tests passing

