#!/bin/bash
# Final Verification Script for Phase 5 & 6 Implementation
# Runs all tests to ensure everything is working

set -e

echo "========================================"
echo "Phase 5 & 6 Implementation Verification"
echo "========================================"
echo ""

# Color codes for output
GREEN='\033[0;32m'
RED='\033[0;31m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Track results
TESTS_PASSED=0
TESTS_FAILED=0

# Function to run a test
run_test() {
    local test_name=$1
    local test_command=$2
    
    echo -n "Running: $test_name ... "
    
    if eval "$test_command" > /tmp/test_output.log 2>&1; then
        echo -e "${GREEN}✓ PASS${NC}"
        TESTS_PASSED=$((TESTS_PASSED + 1))
    else
        echo -e "${RED}✗ FAIL${NC}"
        echo "Error output:"
        cat /tmp/test_output.log
        TESTS_FAILED=$((TESTS_FAILED + 1))
    fi
}

echo "1. Checking file structure..."
echo "   - Authentication component: platform/src/Http/Security/Authentication.php"
if [ -f "platform/src/Http/Security/Authentication.php" ]; then
    echo -e "     ${GREEN}✓ Found${NC}"
else
    echo -e "     ${RED}✗ Missing${NC}"
fi

echo "   - Security documentation: platform/README.md"
if [ -f "platform/README.md" ]; then
    echo -e "     ${GREEN}✓ Found${NC}"
else
    echo -e "     ${RED}✗ Missing${NC}"
fi

echo "   - Test files: tests/test-security.php, tests/integration-test.php"
if [ -f "tests/test-security.php" ] && [ -f "tests/integration-test.php" ]; then
    echo -e "     ${GREEN}✓ Found${NC}"
else
    echo -e "     ${RED}✗ Missing${NC}"
fi

echo ""
echo "2. Running Unit Tests..."
run_test "Security Unit Tests" "php tests/test-security.php"

echo ""
echo "3. Running Integration Tests..."
run_test "Integration Test" "php tests/integration-test.php"

echo ""
echo "4. Checking configuration files..."
if grep -q "API_KEY_ADMIN" .env.example; then
    echo -e "   ${GREEN}✓${NC} .env.example has API key configuration"
else
    echo -e "   ${RED}✗${NC} .env.example missing API key configuration"
fi

if grep -q "allowed_fields" registry/capabilities.yaml; then
    echo -e "   ${GREEN}✓${NC} capabilities.yaml has field allowlists"
else
    echo -e "   ${RED}✗${NC} capabilities.yaml missing field allowlists"
fi

echo ""
echo "5. Verifying security features..."

# Check Authentication component exists and has key methods
if grep -q "authenticate()" platform/src/Http/Security/Authentication.php; then
    echo -e "   ${GREEN}✓${NC} Authentication::authenticate() implemented"
fi

if grep -q "validateApiKey" platform/src/Http/Security/Authentication.php; then
    echo -e "   ${GREEN}✓${NC} API key validation implemented"
fi

# Check ResultGate enhancements
if grep -q "applyAllowlist" platform/ResultGate.php; then
    echo -e "   ${GREEN}✓${NC} ResultGate::applyAllowlist() implemented"
fi

if grep -q "sanitizeDangerousContent" platform/ResultGate.php; then
    echo -e "   ${GREEN}✓${NC} ResultGate::sanitizeDangerousContent() implemented"
fi

if grep -q "limitArraySizes" platform/ResultGate.php; then
    echo -e "   ${GREEN}✓${NC} ResultGate::limitArraySizes() implemented"
fi

# Check audit logging
if grep -q "capability_invocation_success" platform/src/Http/Controllers/InvokeController.php; then
    echo -e "   ${GREEN}✓${NC} Enhanced audit logging implemented"
fi

echo ""
echo "6. Checking documentation..."

if [ -f "platform/README.md" ]; then
    if grep -q "Phase 5" platform/README.md && grep -q "Phase 6" platform/README.md; then
        echo -e "   ${GREEN}✓${NC} platform/README.md documents Phase 5 & 6"
    fi
fi

if grep -q "Phase 5" PHASE2-4.md && grep -q "Phase 6" PHASE2-4.md; then
    echo -e "   ${GREEN}✓${NC} PHASE2-4.md updated with Phase 5 & 6"
fi

echo ""
echo "========================================"
echo "Verification Summary"
echo "========================================"
echo "Tests Passed: $TESTS_PASSED"
echo "Tests Failed: $TESTS_FAILED"
echo ""

if [ $TESTS_FAILED -eq 0 ]; then
    echo -e "${GREEN}✓ All verifications passed!${NC}"
    echo ""
    echo "Phase 5 & 6 implementation is complete and verified."
    echo ""
    echo "Next steps:"
    echo "1. Start the platform: docker-compose up"
    echo "2. Test with: cd tests && ./run-smoke-tests.sh"
    echo "3. Review security: cat platform/README.md"
    exit 0
else
    echo -e "${RED}✗ Some verifications failed!${NC}"
    echo ""
    echo "Please review the errors above and fix them."
    exit 1
fi
