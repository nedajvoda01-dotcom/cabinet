#!/bin/bash
# Phase 6.1 Network Isolation Test
# Tests that adapters are isolated from each other and only accessible to platform

set -e

echo "=== Phase 6.1: Network Isolation Tests ==="
echo

# Start services
echo "Starting services..."
cd /home/runner/work/cabinet/cabinet
docker-compose down 2>/dev/null || true
docker-compose up -d

echo "Waiting for services to be ready..."
sleep 10

# Test 1: Platform can reach adapters
echo
echo "Test 1: Platform can reach adapters (via mesh network)"
docker exec cabinet-platform curl -s -f http://adapter-car-storage/health > /dev/null && echo "✓ Platform can reach car-storage adapter" || echo "✗ FAIL: Platform cannot reach car-storage"
docker exec cabinet-platform curl -s -f http://adapter-pricing/health > /dev/null && echo "✓ Platform can reach pricing adapter" || echo "✗ FAIL: Platform cannot reach pricing"
docker exec cabinet-platform curl -s -f http://adapter-automation/health > /dev/null && echo "✓ Platform can reach automation adapter" || echo "✗ FAIL: Platform cannot reach automation"

# Test 2: Adapters cannot reach each other
echo
echo "Test 2: Adapters cannot reach each other (isolated)"
docker exec cabinet-adapter-car-storage curl -s -f --max-time 3 http://adapter-pricing/health > /dev/null 2>&1 && echo "✗ FAIL: car-storage CAN reach pricing (should be isolated)" || echo "✓ car-storage cannot reach pricing (correctly isolated)"
docker exec cabinet-adapter-pricing curl -s -f --max-time 3 http://adapter-automation/health > /dev/null 2>&1 && echo "✗ FAIL: pricing CAN reach automation (should be isolated)" || echo "✓ pricing cannot reach automation (correctly isolated)"

# Test 3: Adapters cannot reach UI
echo
echo "Test 3: Adapters cannot reach UI (isolated from edge network)"
docker exec cabinet-adapter-car-storage curl -s -f --max-time 3 http://ui-admin > /dev/null 2>&1 && echo "✗ FAIL: adapter CAN reach UI (should be isolated)" || echo "✓ adapter cannot reach UI (correctly isolated)"

# Test 4: UI can reach platform
echo
echo "Test 4: UI can reach platform (via edge network)"
docker exec cabinet-ui-admin curl -s -f http://platform/api/version > /dev/null && echo "✓ UI can reach platform" || echo "✗ FAIL: UI cannot reach platform"

# Test 5: UI cannot reach adapters directly
echo
echo "Test 5: UI cannot reach adapters directly (only through platform)"
docker exec cabinet-ui-admin curl -s -f --max-time 3 http://adapter-car-storage/health > /dev/null 2>&1 && echo "✗ FAIL: UI CAN reach adapter directly (should be blocked)" || echo "✓ UI cannot reach adapters directly (correctly isolated)"

# Test 6: Verify adapters have no published ports (external access blocked)
echo
echo "Test 6: Verify adapters have no published ports"
PUBLISHED_PORTS=$(docker port cabinet-adapter-car-storage | wc -l)
if [ "$PUBLISHED_PORTS" -eq 0 ]; then
    echo "✓ car-storage has no published ports (correctly isolated)"
else
    echo "✗ FAIL: car-storage has published ports: $(docker port cabinet-adapter-car-storage)"
fi

PUBLISHED_PORTS=$(docker port cabinet-adapter-pricing | wc -l)
if [ "$PUBLISHED_PORTS" -eq 0 ]; then
    echo "✓ pricing has no published ports (correctly isolated)"
else
    echo "✗ FAIL: pricing has published ports: $(docker port cabinet-adapter-pricing)"
fi

# Test 7: Host cannot reach adapters directly (only platform)
echo
echo "Test 7: Host cannot reach adapters on their old ports"
curl -s -f --max-time 3 http://localhost:8081/health > /dev/null 2>&1 && echo "✗ FAIL: Host CAN reach adapter on port 8081 (should be blocked)" || echo "✓ Host cannot reach adapters directly (correctly isolated)"

echo
echo "=== Network Isolation Tests Complete ==="
echo
echo "Summary:"
echo "- Platform is on edge + mesh networks"
echo "- Adapters are on mesh network only"
echo "- UI is on edge network only"
echo "- Adapters have no published ports"
echo "- Only platform can communicate with adapters"
