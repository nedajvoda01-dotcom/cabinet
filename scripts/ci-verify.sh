#!/bin/bash
# CI Verification Script - Merge Blockers
# This script runs all tests that must pass before merging

set -e

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

TOTAL_CHECKS=0
FAILED_CHECKS=0

echo -e "${BLUE}========================================"
echo "Cabinet Platform - CI Verification"
echo "All Merge-Blocker Tests"
echo -e "========================================${NC}"
echo ""

run_check() {
    local name=$1
    local command=$2
    
    TOTAL_CHECKS=$((TOTAL_CHECKS + 1))
    
    echo -e "${BLUE}Running: $name${NC}"
    echo "----------------------------------------"
    
    if eval "$command"; then
        echo -e "${GREEN}✓ PASSED: $name${NC}"
        echo ""
        return 0
    else
        echo -e "${RED}✗ FAILED: $name${NC}"
        echo ""
        FAILED_CHECKS=$((FAILED_CHECKS + 1))
        return 1
    fi
}

# 1. Registry Validation
run_check "Registry Validation" \
    "./scripts/validate-registry.sh"

# 2. Architecture Rules
run_check "Architecture Rules" \
    "./scripts/check-architecture.sh"

# 3. Security Tests
run_check "Security Tests" \
    "php tests/test-security.php"

# 4. Integration Tests
run_check "Integration Tests" \
    "php tests/integration-test.php"

# 5. Capability Chain Tests
run_check "Capability Chain Tests" \
    "php tests/test-capability-chains.php"

# 6. Result Profile Tests
run_check "Result Profile Tests" \
    "php tests/test-result-profiles.php"

# 7. Import Idempotency Tests
run_check "Import Idempotency Tests" \
    "php tests/test-import-idempotency.php"

# 8. Network Isolation Tests (requires Docker)
if command -v docker >/dev/null 2>&1; then
    # Check if docker-compose is available
    if command -v docker-compose >/dev/null 2>&1 || docker compose version >/dev/null 2>&1; then
        run_check "Network Isolation Tests" \
            "./tests/test-network-isolation.sh"
    else
        echo -e "${YELLOW}⚠ WARNING: docker-compose not available, skipping network isolation tests${NC}"
        echo ""
    fi
else
    echo -e "${YELLOW}⚠ WARNING: Docker not available, skipping network isolation tests${NC}"
    echo ""
fi

# 9. MVP Verification
run_check "MVP Definition of Done" \
    "./scripts/verify-mvp.sh"

# Summary
echo -e "${BLUE}========================================"
echo "CI Verification Summary"
echo -e "========================================${NC}"
echo "Total Checks: $TOTAL_CHECKS"
echo "Passed: $((TOTAL_CHECKS - FAILED_CHECKS))"
echo "Failed: $FAILED_CHECKS"
echo ""

if [ $FAILED_CHECKS -eq 0 ]; then
    echo -e "${GREEN}✓✓✓ ALL MERGE-BLOCKER TESTS PASSED ✓✓✓${NC}"
    echo ""
    echo "The codebase is ready to merge!"
    echo ""
    echo "Verified:"
    echo "  ✓ Registry is valid and consistent"
    echo "  ✓ Architectural rules are followed"
    echo "  ✓ Security tests pass"
    echo "  ✓ Integration tests pass"
    echo "  ✓ Capability chains work correctly"
    echo "  ✓ Result profiles filter correctly"
    echo "  ✓ Import idempotency is enforced"
    echo "  ✓ Network isolation is in place"
    echo "  ✓ MVP definition of done is complete"
    echo ""
    exit 0
else
    echo -e "${RED}✗✗✗ SOME MERGE-BLOCKER TESTS FAILED ✗✗✗${NC}"
    echo ""
    echo "Please fix the failed tests above before merging."
    echo "All merge-blocker tests must pass."
    echo ""
    exit 1
fi
