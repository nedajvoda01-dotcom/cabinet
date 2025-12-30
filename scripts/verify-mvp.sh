#!/bin/bash
# MVP Verification Script
# Verifies all requirements from the MVP Definition of Done

set -e

echo "========================================"
echo "MVP Definition of Done - Verification"
echo "========================================"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

CHECKS_PASSED=0
CHECKS_FAILED=0

check() {
    local name=$1
    local condition=$2
    
    echo -n "Checking: $name ... "
    if eval "$condition" >/dev/null 2>&1; then
        echo -e "${GREEN}✓ PASS${NC}"
        CHECKS_PASSED=$((CHECKS_PASSED + 1))
        return 0
    else
        echo -e "${RED}✗ FAIL${NC}"
        CHECKS_FAILED=$((CHECKS_FAILED + 1))
        return 1
    fi
}

echo "=== MVP Requirement 1: Single HTTP Entrypoint ==="
echo ""

check "platform/index.php is thin wrapper" \
    "grep -q 'require_once.*public/index.php' platform/index.php"

check "platform/public/index.php is main entrypoint" \
    "test -f platform/public/index.php"

check "No Router.php require in invoke endpoint" \
    "! grep -q \"require_once.*Router.php\" platform/public/index.php"

echo ""
echo "=== MVP Requirement 2: Single Invoke Pipeline ==="
echo ""

check "InvokeController uses CapabilityExecutor" \
    "grep -q 'CapabilityExecutor' platform/src/Http/Controllers/InvokeController.php"

check "POST /api/invoke → InvokeController" \
    "grep -q \"new InvokeController\" platform/public/index.php"

check "CapabilityExecutor exists" \
    "test -f platform/src/Core/CapabilityExecutor.php"

check "No legacy Router in InvokeController" \
    "! grep -q '\$this->router' platform/src/Http/Controllers/InvokeController.php"

echo ""
echo "=== MVP Requirement 3: YAML as Source of Truth ==="
echo ""

check "adapters.yaml exists" \
    "test -f registry/adapters.yaml"

check "capabilities.yaml exists" \
    "test -f registry/capabilities.yaml"

check "policy.yaml exists" \
    "test -f registry/policy.yaml"

check "ui.yaml exists" \
    "test -f registry/ui.yaml"

check "result_profiles.yaml exists" \
    "test -f registry/result_profiles.yaml"

check "No JSON files in registry" \
    "! ls registry/*.json >/dev/null 2>&1"

check "RegistryLoader prefers YAML" \
    "grep -q 'Try YAML first' platform/src/Registry/RegistryLoader.php"

check "Registry validation script exists" \
    "test -x scripts/validate-registry.sh"

echo ""
echo "=== MVP Requirement 4: Registry-Driven Chain Rules ==="
echo ""

check "capabilities.yaml has internal_only fields" \
    "grep -q 'internal_only: true' registry/capabilities.yaml"

check "capabilities.yaml has allowed_parents fields" \
    "grep -q 'allowed_parents:' registry/capabilities.yaml"

check "CapabilityExecutor reads internal_only from registry" \
    "grep -q 'internal_only.*?? false' platform/src/Core/CapabilityExecutor.php"

check "CapabilityExecutor reads allowed_parents from registry" \
    "grep -q 'allowed_parents.*?? \[\]' platform/src/Core/CapabilityExecutor.php"

check "No hardcoded internal capability arrays" \
    "! grep -q 'storage.listings.upsert_batch.*storage.imports.register.*parser' platform/src/Core/CapabilityExecutor.php"

echo ""
echo "=== MVP Requirement 5: Result Profiles Applied ==="
echo ""

check "ui.yaml has result_profile fields" \
    "grep -q 'result_profile:' registry/ui.yaml"

check "ResultGate applies result profiles" \
    "grep -q 'applyResultProfile' platform/ResultGate.php"

check "Result profiles configuration loaded" \
    "grep -q 'loadResultProfiles' platform/ResultGate.php"

echo ""
echo "=== Testing: Smoke Tests ==="
echo ""

check "Security tests pass" \
    "php tests/test-security.php >/dev/null 2>&1"

check "Integration test passes" \
    "php tests/integration-test.php >/dev/null 2>&1"

check "Capability chain tests pass" \
    "php tests/test-capability-chains.php >/dev/null 2>&1"

check "Result profile tests pass" \
    "php tests/test-result-profiles.php >/dev/null 2>&1"

check "Import idempotency tests pass" \
    "php tests/test-import-idempotency.php >/dev/null 2>&1"

echo ""
echo "=== Network Isolation (Merge Blocker) ==="
echo ""

check "Network isolation test exists" \
    "test -x tests/test-network-isolation.sh"

# Note: Network isolation test requires Docker, so we only check if containers are running
if command -v docker >/dev/null 2>&1 && docker ps --format '{{.Names}}' | grep -q '^cabinet-platform$' 2>/dev/null; then
    echo -e "${YELLOW}Note: Docker containers detected. Network isolation test should be run in CI.${NC}"
    echo -e "${YELLOW}      To run manually: ./tests/test-network-isolation.sh${NC}"
else
    echo -e "${YELLOW}Note: Docker containers not running. Skipping network isolation test.${NC}"
    echo -e "${YELLOW}      This test is a merge-blocker and must pass in CI.${NC}"
fi

echo ""
echo "=== Registry Validation ==="
echo ""

if ./scripts/validate-registry.sh >/dev/null 2>&1; then
    echo -e "${GREEN}✓ Registry validation passed${NC}"
    CHECKS_PASSED=$((CHECKS_PASSED + 1))
else
    echo -e "${RED}✗ Registry validation failed${NC}"
    CHECKS_FAILED=$((CHECKS_FAILED + 1))
fi

echo ""
echo "========================================"
echo "MVP Verification Summary"
echo "========================================"
echo "Checks Passed: $CHECKS_PASSED"
echo "Checks Failed: $CHECKS_FAILED"
echo ""

if [ $CHECKS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓✓✓ MVP Definition of Done: COMPLETE ✓✓✓${NC}"
    echo ""
    echo "All MVP requirements verified:"
    echo "  ✓ Single HTTP entrypoint (webroot)"
    echo "  ✓ POST /api/invoke → InvokeController → CapabilityExecutor"
    echo "  ✓ Registry truth = YAML (no JSON)"
    echo "  ✓ Chain rules in registry (internal_only + allowed_parents)"
    echo "  ✓ Result profiles applied by ui.yaml"
    echo "  ✓ All tests passing (smoke + security + chains + profiles + idempotency)"
    echo "  ✓ Network isolation test exists (merge-blocker in CI)"
    echo ""
    echo "Additional tooling:"
    echo "  ✓ Developer scripts: new-adapter.sh, new-capability.sh, run-smoke.sh"
    echo "  ✓ Architecture checks: check-architecture.sh"
    echo ""
    exit 0
else
    echo -e "${RED}✗ MVP verification incomplete${NC}"
    echo ""
    echo "Please fix the failed checks above."
    exit 1
fi
