#!/usr/bin/env php
<?php

/**
 * Master Test Runner
 * 
 * Runs all acceptance criteria tests for the monorepo
 */

echo "╔════════════════════════════════════════════════════════════════╗\n";
echo "║  Cabinet Platform Monorepo - Acceptance Test Suite            ║\n";
echo "╚════════════════════════════════════════════════════════════════╝\n";
echo "\n";

$projectRoot = __DIR__;
$allPassed = true;

// Test 1: Architectural Boundary Tests
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 1: Architectural Boundaries\n";
echo "═══════════════════════════════════════════════════════════════\n";
require_once $projectRoot . '/tests/architecture/boundary-tests.php';
$boundaryTests = new \Cabinet\Tests\Architecture\BoundaryTests($projectRoot);
$passed = $boundaryTests->run();
$allPassed = $allPassed && $passed;
echo "\n";

// Test 2: Contract Parity Tests
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 2: Contract Parity\n";
echo "═══════════════════════════════════════════════════════════════\n";
require_once $projectRoot . '/tests/contracts/parity-tests.php';
$parityTests = new \Cabinet\Tests\Contracts\ParityTests($projectRoot . '/shared/contracts');
$passed = $parityTests->run();
$allPassed = $allPassed && $passed;
echo "\n";

// Test 3: Contract Smoke Tests
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 3: Contract Smoke Tests\n";
echo "═══════════════════════════════════════════════════════════════\n";
require_once $projectRoot . '/tests/contracts/smoke-tests.php';
$smokeTests = new \Cabinet\Tests\Contracts\SmokeTests($projectRoot);
$passed = $smokeTests->run();
$allPassed = $allPassed && $passed;
echo "\n";

// Test 4: Compatibility Check
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 4: Contract Compatibility (N/N-1)\n";
echo "═══════════════════════════════════════════════════════════════\n";
require_once $projectRoot . '/delivery/compat/compatibility-checker.php';
$compatChecker = new \Cabinet\Delivery\Compat\CompatibilityChecker($projectRoot . '/shared/contracts');
$passed = $compatChecker->run();
$allPassed = $allPassed && $passed;
echo "\n";

// Test 5: E2E Smoke Test
echo "═══════════════════════════════════════════════════════════════\n";
echo "TEST 5: E2E Critical Path\n";
echo "═══════════════════════════════════════════════════════════════\n";
require_once $projectRoot . '/tests/e2e-smoke/critical-path.php';
$e2eTests = new \Cabinet\Tests\E2E\CriticalPathSmokeTest();
$passed = $e2eTests->run();
$allPassed = $allPassed && $passed;
echo "\n";

// Final Summary
echo "═══════════════════════════════════════════════════════════════\n";
echo "FINAL SUMMARY\n";
echo "═══════════════════════════════════════════════════════════════\n";

if ($allPassed) {
    echo "✅ ALL ACCEPTANCE TESTS PASSED!\n";
    echo "\n";
    echo "The Cabinet Platform Monorepo meets all acceptance criteria:\n";
    echo "  ✓ Architectural boundaries respected\n";
    echo "  ✓ Contract parity maintained\n";
    echo "  ✓ Contract usage validated\n";
    echo "  ✓ N/N-1 compatibility verified\n";
    echo "  ✓ Critical path functional\n";
    echo "\n";
    echo "Ready for deployment! 🚀\n";
    exit(0);
} else {
    echo "❌ SOME TESTS FAILED\n";
    echo "\n";
    echo "Please fix the failing tests before proceeding.\n";
    echo "See output above for details.\n";
    exit(1);
}
