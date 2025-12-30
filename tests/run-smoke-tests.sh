#!/bin/bash
# Smoke test script for Cabinet Platform
# Phase 5: Updated with API key authentication

set -e

PLATFORM_URL="http://localhost:8080/api/invoke"
ADMIN_API_KEY="admin_secret_key_12345"
PUBLIC_API_KEY="public_secret_key_67890"
FAILED=0
PASSED=0

echo "=== Cabinet Platform Smoke Tests ==="
echo ""

test_request() {
    local test_name=$1
    local payload=$2
    local should_succeed=$3
    local api_key=$4
    
    echo -n "Testing: $test_name ... "
    
    response=$(curl -s -w "\n%{http_code}" -X POST "$PLATFORM_URL" \
        -H "Content-Type: application/json" \
        -H "X-API-Key: $api_key" \
        -d "$payload")
    
    http_code=$(echo "$response" | tail -n1)
    body=$(echo "$response" | head -n-1)
    
    if [ "$should_succeed" = "true" ]; then
        if [ "$http_code" = "200" ] && echo "$body" | grep -q '"success":true'; then
            echo "✓ PASSED"
            PASSED=$((PASSED + 1))
        else
            echo "✗ FAILED (Expected success, got $http_code)"
            echo "Response: $body"
            FAILED=$((FAILED + 1))
        fi
    else
        if echo "$body" | grep -q '"error":true'; then
            echo "✓ PASSED (correctly rejected)"
            PASSED=$((PASSED + 1))
        else
            echo "✗ FAILED (Should have been rejected)"
            echo "Response: $body"
            FAILED=$((FAILED + 1))
        fi
    fi
}

echo "=== Testing Car Management ==="
echo ""

# Test 1: List cars (public - allowed)
test_request "List cars (public)" '{
  "capability": "car.list",
  "payload": {}
}' "true" "$PUBLIC_API_KEY"

# Test 2: Create car (admin - allowed)
test_request "Create car (admin)" '{
  "capability": "car.create",
  "payload": {
    "brand": "Toyota",
    "model": "Camry",
    "year": 2024,
    "price": 35000
  }
}' "true" "$ADMIN_API_KEY"

# Test 3: Create car (public - should fail)
test_request "Create car (public - should fail)" '{
  "capability": "car.create",
  "payload": {
    "brand": "Honda",
    "model": "Accord",
    "year": 2024,
    "price": 32000
  }
}' "false" "$PUBLIC_API_KEY"

echo ""
echo "=== Testing Pricing ==="
echo ""

# Test 4: Calculate price (public - allowed)
test_request "Calculate price (public)" '{
  "capability": "price.calculate",
  "payload": {
    "brand": "Toyota",
    "year": 2020,
    "base_price": 35000
  }
}' "true" "$PUBLIC_API_KEY"

# Test 5: List pricing rules (admin - allowed)
test_request "List pricing rules (admin)" '{
  "capability": "price.rule.list",
  "payload": {}
}' "true" "$ADMIN_API_KEY"

echo ""
echo "=== Testing Automation ==="
echo ""

# Test 6: Execute workflow (admin - allowed)
test_request "Execute workflow (admin)" '{
  "capability": "workflow.execute",
  "payload": {
    "workflow_id": "car_onboarding"
  }
}' "true" "$ADMIN_API_KEY"

# Test 7: List workflows (admin - allowed)
test_request "List workflows (admin)" '{
  "capability": "workflow.list",
  "payload": {}
}' "true" "$ADMIN_API_KEY"

echo ""
echo "=== Testing Error Cases ==="
echo ""

# Test 8: Invalid capability
test_request "Invalid capability (should fail)" '{
  "capability": "invalid.capability",
  "payload": {}
}' "false" "$ADMIN_API_KEY"

# Test 9: Missing capability
test_request "Missing capability (should fail)" '{
  "payload": {}
}' "false" "$ADMIN_API_KEY"

echo ""
echo "==================================="
echo "Test Results:"
echo "  Passed: $PASSED"
echo "  Failed: $FAILED"
echo "==================================="

if [ $FAILED -gt 0 ]; then
    exit 1
fi

exit 0
