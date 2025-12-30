<?php
/**
 * Phase 6.2: Test capability chain enforcement
 * Tests that internal capabilities can only be called from allowed parent capabilities
 */

require_once __DIR__ . '/../platform/Storage.php';
require_once __DIR__ . '/../platform/Policy.php';
require_once __DIR__ . '/../platform/Limits.php';
require_once __DIR__ . '/../platform/ResultGate.php';
require_once __DIR__ . '/../platform/Router.php';
require_once __DIR__ . '/../platform/src/Core/CapabilityExecutor.php';

use Platform\Core\CapabilityExecutor;

echo "=== Phase 6.2: Capability Chain Enforcement Tests ===\n\n";

// Initialize components
$registryPath = __DIR__ . '/../registry';
$storagePath = '/tmp/cabinet-test-storage';

// Clean and create storage
if (is_dir($storagePath)) {
    shell_exec("rm -rf $storagePath");
}
mkdir($storagePath, 0755, true);

$storage = new Storage($storagePath);
$policy = new Policy($registryPath . '/policy.yaml');
$limits = new Limits($policy, $storage, ['default_timeout' => 30]);

// Load configurations
$capabilitiesConfig = yaml_parse_file($registryPath . '/capabilities.yaml');
$uiConfig = yaml_parse_file($registryPath . '/ui.yaml');

$resultGateConfig = [
    'max_response_size' => 10485760,
    'max_array_size' => 1000,
    'registry_path' => $registryPath
];
$resultGate = new ResultGate($policy, $capabilitiesConfig, $resultGateConfig);

$router = new Router(
    $registryPath . '/adapters.yaml',
    $registryPath . '/capabilities.yaml'
);

$executor = new CapabilityExecutor($router, $policy, $limits, $resultGate, $storage, $uiConfig);

$testsPassed = 0;
$testsFailed = 0;

// Test 1: Direct call to internal capability should fail
echo "Test 1: Direct call to internal capability (storage.listings.upsert_batch) should fail\n";
try {
    $actor = ['user_id' => 'test_user', 'role' => 'admin', 'ui' => 'admin'];
    $result = $executor->executeCapability($actor, 'storage.listings.upsert_batch', [
        'listings' => []
    ]);
    echo "✗ FAIL: Internal capability was allowed (should be blocked)\n";
    $testsFailed++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'Access denied') !== false) {
        echo "✓ PASS: Internal capability correctly blocked\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong error: {$e->getMessage()}\n";
        $testsFailed++;
    }
}

echo "\n";

// Test 2: Chained call from allowed parent should work
echo "Test 2: Chained call to internal capability from allowed parent should work\n";
try {
    // Simulate internal call context
    $actor = ['user_id' => 'system', 'role' => 'admin', 'ui' => 'internal'];
    $context = ['parent_capability' => 'import.run'];
    
    // Note: This test would pass policy checks but fail at adapter level since we're not running the full stack
    // For unit testing, we're just verifying the policy enforcement logic
    
    // Instead, let's test the internal methods directly
    $reflector = new ReflectionClass($executor);
    $method = $reflector->getMethod('validateCapabilityAccess');
    $method->setAccessible(true);
    
    $isAllowed = $method->invoke($executor, 'storage.listings.upsert_batch', 'internal', 'admin', $context);
    
    if ($isAllowed) {
        echo "✓ PASS: Internal capability allowed when called from authorized parent\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Internal capability blocked even with valid parent\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error during test: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 3: Chained call from unauthorized parent should fail
echo "Test 3: Chained call to internal capability from unauthorized parent should fail\n";
try {
    $reflector = new ReflectionClass($executor);
    $method = $reflector->getMethod('validateCapabilityAccess');
    $method->setAccessible(true);
    
    $context = ['parent_capability' => 'car.create']; // Unauthorized parent
    $isAllowed = $method->invoke($executor, 'storage.listings.upsert_batch', 'internal', 'admin', $context);
    
    if (!$isAllowed) {
        echo "✓ PASS: Internal capability correctly blocked from unauthorized parent\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Internal capability allowed from unauthorized parent\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error during test: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 4: Check that allowed chain mapping is correct
echo "Test 4: Verify allowed capability chains are correctly defined\n";
try {
    $reflector = new ReflectionClass($executor);
    $method = $reflector->getMethod('isAllowedChain');
    $method->setAccessible(true);
    
    // Test valid chain
    $isValid = $method->invoke($executor, 'import.run', 'storage.listings.upsert_batch');
    if ($isValid) {
        echo "✓ PASS: Valid chain (import.run → storage.listings.upsert_batch) recognized\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Valid chain not recognized\n";
        $testsFailed++;
    }
    
    // Test invalid chain
    $isValid = $method->invoke($executor, 'car.create', 'storage.listings.upsert_batch');
    if (!$isValid) {
        echo "✓ PASS: Invalid chain correctly rejected\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Invalid chain was accepted\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error during test: {$e->getMessage()}\n";
    $testsFailed += 2;
}

echo "\n";

// Test 5: Public capability can be called directly
echo "Test 5: Public capability (car.list) can be called directly\n";
try {
    $reflector = new ReflectionClass($executor);
    $method = $reflector->getMethod('isInternalOnlyCapability');
    $method->setAccessible(true);
    
    $isInternal = $method->invoke($executor, 'car.list');
    
    if (!$isInternal) {
        echo "✓ PASS: Public capability not marked as internal-only\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Public capability marked as internal-only\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error during test: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
