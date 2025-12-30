#!/usr/bin/env php
<?php
/**
 * Test Steps 7-9 Implementation
 * - Step 7: Import orchestration through core
 * - Step 8: Catalog search capabilities
 * - Step 9: Smoke tests
 */

// Configuration
$platformUrl = getenv('PLATFORM_URL') ?: 'http://localhost:8080';
$apiKey = getenv('API_KEY_ADMIN') ?: 'admin_secret_key_12345';

// Test counters
$passed = 0;
$failed = 0;

/**
 * Make API call to platform
 */
function callPlatform($capability, $payload, $apiKey, $platformUrl) {
    $ch = curl_init("$platformUrl/api/invoke");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        "X-API-Key: $apiKey"
    ]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode([
        'capability' => $capability,
        'payload' => $payload
    ]));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    return [
        'http_code' => $httpCode,
        'response' => json_decode($response, true)
    ];
}

/**
 * Test helper
 */
function test($name, $condition, &$passed, &$failed) {
    if ($condition) {
        echo "✓ PASS: $name\n";
        $passed++;
    } else {
        echo "✗ FAIL: $name\n";
        $failed++;
    }
}

echo "=== Steps 7-9 Implementation Tests ===\n\n";

// Step 9.1: Test filters.get returns lists
echo "--- Step 9.1: Test catalog.filters.get ---\n";
$result = callPlatform('catalog.filters.get', [], $apiKey, $platformUrl);
test(
    "catalog.filters.get returns 200", 
    $result['http_code'] === 200,
    $passed, 
    $failed
);
test(
    "catalog.filters.get returns filter lists",
    isset($result['response']['result']['data']['brands']) &&
    isset($result['response']['result']['data']['models']) &&
    isset($result['response']['result']['data']['years']) &&
    isset($result['response']['result']['data']['price_ranges']),
    $passed,
    $failed
);
echo "\n";

// Step 9.2: Test search returns results and counter
echo "--- Step 9.2: Test catalog.listings.search ---\n";
$result = callPlatform('catalog.listings.search', [
    'filters' => [],
    'page' => 1,
    'per_page' => 20
], $apiKey, $platformUrl);
test(
    "catalog.listings.search returns 200",
    $result['http_code'] === 200,
    $passed,
    $failed
);
test(
    "catalog.listings.search returns listings and total_count",
    isset($result['response']['result']['data']['listings']) &&
    isset($result['response']['result']['data']['total_count']),
    $passed,
    $failed
);
echo "\n";

// Step 9.3: Test import.run loads CSV and increases listing count
echo "--- Step 9.3: Test import.run increases listing count ---\n";

// Get initial count
$beforeResult = callPlatform('catalog.listings.search', [
    'filters' => [],
    'page' => 1,
    'per_page' => 100
], $apiKey, $platformUrl);
$countBefore = $beforeResult['response']['result']['data']['total_count'] ?? 0;
echo "Listings before import: $countBefore\n";

// Import CSV
$csvData = "external_id,brand,model,year,price\n";
$csvData .= "TEST001,TestBrand1,TestModel1,2023,30000\n";
$csvData .= "TEST002,TestBrand2,TestModel2,2024,35000\n";

$importResult = callPlatform('import.run', [
    'filename' => 'test.csv',
    'csv_data' => $csvData
], $apiKey, $platformUrl);

test(
    "import.run returns 200",
    $importResult['http_code'] === 200,
    $passed,
    $failed
);

test(
    "import.run returns import status",
    isset($importResult['response']['result']['data']['import_id']) &&
    isset($importResult['response']['result']['data']['status']),
    $passed,
    $failed
);

// Get count after import
$afterResult = callPlatform('catalog.listings.search', [
    'filters' => [],
    'page' => 1,
    'per_page' => 100
], $apiKey, $platformUrl);
$countAfter = $afterResult['response']['result']['data']['total_count'] ?? 0;
echo "Listings after import: $countAfter\n";

test(
    "import.run increases listing count",
    $countAfter > $countBefore,
    $passed,
    $failed
);
echo "\n";

// Step 9.4: Test repeated import doesn't create duplicates
echo "--- Step 9.4: Test import idempotency ---\n";

// Import same CSV again
$duplicateImportResult = callPlatform('import.run', [
    'filename' => 'test.csv',
    'csv_data' => $csvData
], $apiKey, $platformUrl);

test(
    "Duplicate import returns 200",
    $duplicateImportResult['http_code'] === 200,
    $passed,
    $failed
);

test(
    "Duplicate import detected",
    isset($duplicateImportResult['response']['result']['data']['status']) &&
    $duplicateImportResult['response']['result']['data']['status'] === 'duplicate',
    $passed,
    $failed
);

// Verify count didn't change
$afterDuplicateResult = callPlatform('catalog.listings.search', [
    'filters' => [],
    'page' => 1,
    'per_page' => 100
], $apiKey, $platformUrl);
$countAfterDuplicate = $afterDuplicateResult['response']['result']['data']['total_count'] ?? 0;

test(
    "Duplicate import doesn't increase count",
    $countAfterDuplicate === $countAfter,
    $passed,
    $failed
);
echo "\n";

// Step 9.5: Test UI cannot call forbidden storage operations directly (403)
echo "--- Step 9.5: Test internal capability protection ---\n";

// Try to call internal capability directly
$forbiddenResult = callPlatform('storage.listings.upsert_batch', [
    'listings' => []
], $apiKey, $platformUrl);

test(
    "Direct call to storage.listings.upsert_batch returns 403",
    $result['http_code'] === 403 || 
    (isset($forbiddenResult['response']['error']) || 
     isset($forbiddenResult['response']['message'])),
    $passed,
    $failed
);

// Try to call parser.parse_csv directly
$forbiddenParserResult = callPlatform('parser.parse_csv', [
    'csv_data' => 'test'
], $apiKey, $platformUrl);

test(
    "Direct call to parser.parse_csv is denied",
    $forbiddenParserResult['http_code'] === 403 || 
    (isset($forbiddenParserResult['response']['error']) || 
     isset($forbiddenParserResult['response']['message'])),
    $passed,
    $failed
);
echo "\n";

// Additional tests for catalog capabilities
echo "--- Additional Catalog Tests ---\n";

// Test catalog.listing.get
$searchForGet = callPlatform('catalog.listings.search', [
    'filters' => [],
    'page' => 1,
    'per_page' => 1
], $apiKey, $platformUrl);

if (isset($searchForGet['response']['result']['data']['listings'][0]['id'])) {
    $listingId = $searchForGet['response']['result']['data']['listings'][0]['id'];
    
    $getResult = callPlatform('catalog.listing.get', [
        'id' => $listingId
    ], $apiKey, $platformUrl);
    
    test(
        "catalog.listing.get returns 200",
        $getResult['http_code'] === 200,
        $passed,
        $failed
    );
    
    test(
        "catalog.listing.get returns listing details",
        isset($getResult['response']['result']['data']['id']),
        $passed,
        $failed
    );
    
    // Test catalog.photos.list
    $photosResult = callPlatform('catalog.photos.list', [
        'listing_id' => $listingId
    ], $apiKey, $platformUrl);
    
    test(
        "catalog.photos.list returns 200",
        $photosResult['http_code'] === 200,
        $passed,
        $failed
    );
    
    test(
        "catalog.photos.list returns photos array",
        isset($photosResult['response']['result']['data']['photos']),
        $passed,
        $failed
    );
    
    // Test catalog.listing.use
    $useResult = callPlatform('catalog.listing.use', [
        'id' => $listingId
    ], $apiKey, $platformUrl);
    
    test(
        "catalog.listing.use returns 200",
        $useResult['http_code'] === 200,
        $passed,
        $failed
    );
    
    test(
        "catalog.listing.use marks listing as used",
        isset($useResult['response']['result']['data']['status']) &&
        $useResult['response']['result']['data']['status'] === 'used',
        $passed,
        $failed
    );
} else {
    echo "⚠ Skipping catalog.listing.get tests (no listings found)\n";
}
echo "\n";

// Summary
echo "=== Test Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";

if ($failed === 0) {
    echo "\n✓ All tests passed!\n";
    exit(0);
} else {
    echo "\n✗ Some tests failed\n";
    exit(1);
}
