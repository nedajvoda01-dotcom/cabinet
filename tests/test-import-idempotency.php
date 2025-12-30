<?php
/**
 * Phase 6.4: Test import idempotency
 * Tests that duplicate CSV imports are detected and prevented
 */

require_once __DIR__ . '/../platform/Storage.php';

echo "=== Phase 6.4: Import Idempotency Tests ===\n\n";

// Initialize storage
$storagePath = '/tmp/cabinet-test-import-storage';

// Clean and create storage
if (is_dir($storagePath)) {
    shell_exec("rm -rf $storagePath");
}
mkdir($storagePath, 0755, true);

$storage = new Storage($storagePath);

$testsPassed = 0;
$testsFailed = 0;

// Sample CSV content
$csvContent = "external_id,brand,model,year,price\n";
$csvContent .= "EXT001,Toyota,Camry,2020,25000\n";
$csvContent .= "EXT002,Honda,Accord,2021,28000\n";
$csvContent .= "EXT003,Ford,Mustang,2019,35000\n";

$contentHash = hash('sha256', $csvContent);

// Test 1: First import should be registered as new
echo "Test 1: First import registration should return 'new' status\n";
try {
    $result = $storage->registerImport($contentHash, 'test_import.csv', 'inbox');
    
    if ($result['status'] === 'new' && isset($result['import_id'])) {
        echo "✓ PASS: First import registered successfully\n";
        echo "  Import ID: {$result['import_id']}\n";
        $testsPassed++;
        $firstImportId = $result['import_id'];
    } else {
        echo "✗ FAIL: First import not registered correctly\n";
        echo "  Status: {$result['status']}\n";
        $testsFailed++;
        $firstImportId = null;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error: {$e->getMessage()}\n";
    $testsFailed++;
    $firstImportId = null;
}

echo "\n";

// Test 2: Mark import as done
echo "Test 2: Mark import as completed\n";
try {
    $stats = [
        'created' => 3,
        'updated' => 0,
        'failed' => 0
    ];
    
    $storage->markImportDone($contentHash, $stats);
    echo "✓ PASS: Import marked as done\n";
    $testsPassed++;
} catch (Exception $e) {
    echo "✗ FAIL: Error: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 3: Duplicate import should be detected
echo "Test 3: Duplicate import should be detected\n";
try {
    $result = $storage->registerImport($contentHash, 'test_import.csv', 'inbox');
    
    if ($result['status'] === 'duplicate') {
        echo "✓ PASS: Duplicate import detected\n";
        echo "  Message: {$result['message']}\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Duplicate import not detected\n";
        echo "  Status: {$result['status']}\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 4: Different content should be registered as new
echo "Test 4: Different content should be registered as new\n";
try {
    $differentCsv = "external_id,brand,model,year,price\n";
    $differentCsv .= "EXT004,Tesla,Model 3,2022,45000\n";
    
    $differentHash = hash('sha256', $differentCsv);
    $result = $storage->registerImport($differentHash, 'test_import2.csv', 'inbox');
    
    if ($result['status'] === 'new') {
        echo "✓ PASS: Different content registered as new\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Different content not registered as new\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 5: Upsert listing (create)
echo "Test 5: Upsert listing (create new)\n";
try {
    $GLOBALS['current_user_id'] = 'test_user';
    
    $listing = [
        'external_id' => 'EXT001',
        'brand' => 'Toyota',
        'model' => 'Camry',
        'year' => 2020,
        'price' => 25000
    ];
    
    $result = $storage->upsertListing($listing);
    
    if ($result['action'] === 'created' && isset($result['id'])) {
        echo "✓ PASS: Listing created successfully\n";
        echo "  ID: {$result['id']}\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Listing not created\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 6: Upsert listing (update existing)
echo "Test 6: Upsert listing (update existing)\n";
try {
    $listing = [
        'external_id' => 'EXT001',
        'brand' => 'Toyota',
        'model' => 'Camry',
        'year' => 2020,
        'price' => 26000, // Updated price
        'status' => 'sold'
    ];
    
    $result = $storage->upsertListing($listing);
    
    if ($result['action'] === 'updated') {
        echo "✓ PASS: Listing updated successfully\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Listing not updated (got action: {$result['action']})\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 7: Batch upsert
echo "Test 7: Batch upsert listings\n";
try {
    $listings = [
        [
            'external_id' => 'EXT002',
            'brand' => 'Honda',
            'model' => 'Accord',
            'year' => 2021,
            'price' => 28000
        ],
        [
            'external_id' => 'EXT003',
            'brand' => 'Ford',
            'model' => 'Mustang',
            'year' => 2019,
            'price' => 35000
        ],
        [
            'external_id' => 'EXT001', // Existing one
            'brand' => 'Toyota',
            'model' => 'Camry',
            'year' => 2020,
            'price' => 27000 // Updated again
        ]
    ];
    
    $result = $storage->upsertListingsBatch($listings);
    
    if ($result['created'] === 2 && $result['updated'] === 1 && $result['failed'] === 0) {
        echo "✓ PASS: Batch upsert successful\n";
        echo "  Created: {$result['created']}, Updated: {$result['updated']}, Failed: {$result['failed']}\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Batch upsert results incorrect\n";
        echo "  Created: {$result['created']}, Updated: {$result['updated']}, Failed: {$result['failed']}\n";
        echo "  Expected: Created: 2, Updated: 1, Failed: 0\n";
        $testsFailed++;
    }
} catch (Exception $e) {
    echo "✗ FAIL: Error: {$e->getMessage()}\n";
    $testsFailed++;
}

echo "\n";

// Test 8: Import with missing external_id should fail
echo "Test 8: Upsert without external_id should fail\n";
try {
    $listing = [
        'brand' => 'Tesla',
        'model' => 'Model S'
    ];
    
    $result = $storage->upsertListing($listing);
    echo "✗ FAIL: Upsert without external_id should have failed\n";
    $testsFailed++;
} catch (Exception $e) {
    if (strpos($e->getMessage(), 'external_id') !== false) {
        echo "✓ PASS: Upsert correctly requires external_id\n";
        $testsPassed++;
    } else {
        echo "✗ FAIL: Wrong error: {$e->getMessage()}\n";
        $testsFailed++;
    }
}

echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Passed: $testsPassed\n";
echo "Failed: $testsFailed\n";

if ($testsFailed === 0) {
    echo "\n✓ All tests passed!\n";
    echo "\nImport Idempotency Implementation:\n";
    echo "- Content hash (SHA256) prevents duplicate imports\n";
    echo "- Upsert by external_id prevents duplicate records\n";
    echo "- Batch operations support efficient imports\n";
    echo "- Import tracking maintains audit trail\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed!\n";
    exit(1);
}
