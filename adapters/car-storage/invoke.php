<?php
/**
 * Car Storage Adapter
 * Handles car data storage and retrieval
 */

// Simple in-memory storage (in production, use a real database)
$dataFile = '/tmp/car-storage.json';

function loadData(): array {
    global $dataFile;
    if (!file_exists($dataFile)) {
        return [];
    }
    return json_decode(file_get_contents($dataFile), true) ?? [];
}

function saveData(array $data): void {
    global $dataFile;
    file_put_contents($dataFile, json_encode($data, JSON_PRETTY_PRINT));
}

// Handle request
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Health check
if ($path === '/health') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'adapter' => 'car-storage']);
    exit;
}

// Invoke endpoint
if ($path === '/invoke' && $method === 'POST') {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    // Phase 4: Standardized adapter protocol
    $capability = $request['capability'] ?? '';
    $payload = $request['payload'] ?? [];
    $traceId = $request['trace_id'] ?? uniqid('trace_');
    $actor = $request['actor'] ?? ['user_id' => 'unknown', 'role' => 'guest', 'ui' => 'unknown'];
    
    $data = loadData();
    
    try {
        switch ($capability) {
            case 'car.create':
                $carId = uniqid('car_');
                $car = $payload;
                $car['id'] = $carId;
                $car['created_at'] = time();
                $car['created_by'] = $actor['user_id'];
                $data[$carId] = $car;
                saveData($data);
                $result = ['id' => $carId, 'car' => $car];
                break;
                
            case 'car.read':
                $carId = $payload['id'] ?? '';
                if (!isset($data[$carId])) {
                    throw new Exception('Car not found');
                }
                $result = $data[$carId];
                break;
                
            case 'car.update':
                $carId = $payload['id'] ?? '';
                if (!isset($data[$carId])) {
                    throw new Exception('Car not found');
                }
                $data[$carId] = array_merge($data[$carId], $payload);
                $data[$carId]['updated_at'] = time();
                $data[$carId]['updated_by'] = $actor['user_id'];
                saveData($data);
                $result = $data[$carId];
                break;
                
            case 'car.delete':
                $carId = $payload['id'] ?? '';
                if (!isset($data[$carId])) {
                    throw new Exception('Car not found');
                }
                unset($data[$carId]);
                saveData($data);
                $result = ['deleted' => true, 'id' => $carId];
                break;
                
            case 'car.list':
                $result = array_values($data);
                break;
            
            // Phase 6.4: Import capabilities
            case 'import.run':
                // Simulated CSV import with hash-based idempotency
                $filename = $payload['filename'] ?? 'unknown.csv';
                $csvData = $payload['csv_data'] ?? '';
                
                // Calculate content hash
                $contentHash = hash('sha256', $csvData);
                
                // Register import (check for duplicates)
                // In real implementation, this would call storage adapter
                // For simulation, we'll use a simple file-based tracking
                $importTrackingFile = '/tmp/imports-tracking.json';
                $importTracking = file_exists($importTrackingFile) 
                    ? json_decode(file_get_contents($importTrackingFile), true) 
                    : [];
                
                if (isset($importTracking[$contentHash])) {
                    // Already imported
                    $result = [
                        'import_id' => $importTracking[$contentHash]['import_id'],
                        'status' => 'duplicate',
                        'message' => 'File already imported',
                        'filename' => $filename,
                        'records_created' => 0,
                        'records_updated' => 0,
                        'records_failed' => 0
                    ];
                    break;
                }
                
                // Parse CSV and import
                $importId = uniqid('import_');
                $lines = array_filter(explode("\n", trim($csvData)));
                $headers = str_getcsv(array_shift($lines));
                
                $created = 0;
                $updated = 0;
                $failed = 0;
                
                foreach ($lines as $line) {
                    try {
                        $row = str_getcsv($line);
                        $listing = array_combine($headers, $row);
                        
                        // Upsert logic (simplified)
                        $externalId = $listing['external_id'] ?? uniqid('ext_');
                        $listingId = 'listing_' . md5($externalId);
                        
                        if (isset($data[$listingId])) {
                            $updated++;
                            $data[$listingId] = array_merge($data[$listingId], $listing);
                            $data[$listingId]['updated_at'] = time();
                            $data[$listingId]['updated_by'] = $actor['user_id'];
                        } else {
                            $created++;
                            $listing['id'] = $listingId;
                            $listing['external_id'] = $externalId;
                            $listing['created_at'] = time();
                            $listing['created_by'] = $actor['user_id'];
                            $data[$listingId] = $listing;
                        }
                    } catch (Exception $e) {
                        $failed++;
                    }
                }
                
                saveData($data);
                
                // Mark import as done
                $importTracking[$contentHash] = [
                    'import_id' => $importId,
                    'filename' => $filename,
                    'content_hash' => $contentHash,
                    'status' => 'done',
                    'created' => $created,
                    'updated' => $updated,
                    'failed' => $failed,
                    'imported_at' => time()
                ];
                file_put_contents($importTrackingFile, json_encode($importTracking, JSON_PRETTY_PRINT));
                
                $result = [
                    'import_id' => $importId,
                    'status' => 'completed',
                    'filename' => $filename,
                    'records_created' => $created,
                    'records_updated' => $updated,
                    'records_failed' => $failed,
                    'message' => "Import completed: $created created, $updated updated, $failed failed"
                ];
                break;
            
            case 'storage.imports.register':
                // Internal capability for import registration
                $contentHash = $payload['content_hash'] ?? '';
                $filename = $payload['filename'] ?? '';
                
                $importTrackingFile = '/tmp/imports-tracking.json';
                $importTracking = file_exists($importTrackingFile) 
                    ? json_decode(file_get_contents($importTrackingFile), true) 
                    : [];
                
                if (isset($importTracking[$contentHash])) {
                    $result = [
                        'status' => 'duplicate',
                        'import_id' => $importTracking[$contentHash]['import_id'],
                        'message' => 'Import already exists'
                    ];
                } else {
                    $importId = uniqid('import_');
                    $importTracking[$contentHash] = [
                        'import_id' => $importId,
                        'filename' => $filename,
                        'content_hash' => $contentHash,
                        'status' => 'pending',
                        'started_at' => time()
                    ];
                    file_put_contents($importTrackingFile, json_encode($importTracking, JSON_PRETTY_PRINT));
                    
                    $result = [
                        'status' => 'new',
                        'import_id' => $importId
                    ];
                }
                break;
            
            case 'storage.imports.mark_done':
                // Internal capability to mark import as done
                $contentHash = $payload['content_hash'] ?? '';
                $stats = $payload['stats'] ?? [];
                
                $importTrackingFile = '/tmp/imports-tracking.json';
                $importTracking = file_exists($importTrackingFile) 
                    ? json_decode(file_get_contents($importTrackingFile), true) 
                    : [];
                
                if (isset($importTracking[$contentHash])) {
                    $importTracking[$contentHash]['status'] = 'done';
                    $importTracking[$contentHash]['finished_at'] = time();
                    $importTracking[$contentHash]['stats'] = $stats;
                    file_put_contents($importTrackingFile, json_encode($importTracking, JSON_PRETTY_PRINT));
                    
                    $result = [
                        'import_id' => $importTracking[$contentHash]['import_id'],
                        'status' => 'done'
                    ];
                } else {
                    throw new Exception('Import not found');
                }
                break;
            
            case 'storage.listings.upsert_batch':
                // Internal capability for batch upsert
                $listings = $payload['listings'] ?? [];
                
                $created = 0;
                $updated = 0;
                $failed = 0;
                $errors = [];
                
                foreach ($listings as $listing) {
                    try {
                        $externalId = $listing['external_id'] ?? null;
                        if (!$externalId) {
                            throw new Exception('external_id required');
                        }
                        
                        $listingId = 'listing_' . md5($externalId);
                        
                        if (isset($data[$listingId])) {
                            $updated++;
                            $data[$listingId] = array_merge($data[$listingId], $listing);
                            $data[$listingId]['updated_at'] = time();
                            $data[$listingId]['updated_by'] = $actor['user_id'];
                        } else {
                            $created++;
                            $listing['id'] = $listingId;
                            $listing['created_at'] = time();
                            $listing['created_by'] = $actor['user_id'];
                            $data[$listingId] = $listing;
                        }
                    } catch (Exception $e) {
                        $failed++;
                        $errors[] = [
                            'listing' => $listing,
                            'error' => $e->getMessage()
                        ];
                    }
                }
                
                saveData($data);
                
                $result = [
                    'created' => $created,
                    'updated' => $updated,
                    'failed' => $failed,
                    'errors' => $errors
                ];
                break;
            
            // Step 7: Parser capability (internal-only)
            case 'parser.parse_csv':
                // Parse CSV data and normalize listings
                $csvData = $payload['csv_data'] ?? '';
                $filename = $payload['filename'] ?? 'unknown.csv';
                
                $lines = array_filter(explode("\n", trim($csvData)));
                if (empty($lines)) {
                    throw new Exception('CSV data is empty');
                }
                
                $headers = str_getcsv(array_shift($lines));
                $listings = [];
                $photos = [];
                $errors = [];
                
                foreach ($lines as $lineNum => $line) {
                    try {
                        $row = str_getcsv($line);
                        if (count($row) !== count($headers)) {
                            throw new Exception('Column count mismatch');
                        }
                        $listing = array_combine($headers, $row);
                        
                        // Ensure external_id exists
                        if (!isset($listing['external_id']) || empty($listing['external_id'])) {
                            $listing['external_id'] = uniqid('ext_');
                        }
                        
                        $listings[] = $listing;
                    } catch (Exception $e) {
                        $errors[] = [
                            'line' => $lineNum + 2, // +2 for header and 0-index
                            'error' => $e->getMessage()
                        ];
                    }
                }
                
                $result = [
                    'listings' => $listings,
                    'photos' => $photos,
                    'report' => [
                        'total_lines' => count($lines),
                        'parsed' => count($listings),
                        'failed' => count($errors)
                    ],
                    'errors' => $errors
                ];
                break;
            
            // Step 8: Catalog capabilities
            case 'catalog.filters.get':
                // Get available filters from existing data
                $brands = [];
                $models = [];
                $years = [];
                $statuses = [];
                
                foreach ($data as $item) {
                    if (isset($item['brand']) && !in_array($item['brand'], $brands)) {
                        $brands[] = $item['brand'];
                    }
                    if (isset($item['model']) && !in_array($item['model'], $models)) {
                        $models[] = $item['model'];
                    }
                    if (isset($item['year']) && !in_array($item['year'], $years)) {
                        $years[] = (int)$item['year'];
                    }
                    if (isset($item['status']) && !in_array($item['status'], $statuses)) {
                        $statuses[] = $item['status'];
                    }
                }
                
                sort($brands);
                sort($models);
                sort($years);
                sort($statuses);
                
                $result = [
                    'brands' => $brands,
                    'models' => $models,
                    'years' => $years,
                    'price_ranges' => [
                        ['min' => 0, 'max' => 20000, 'label' => 'Under $20k'],
                        ['min' => 20000, 'max' => 40000, 'label' => '$20k - $40k'],
                        ['min' => 40000, 'max' => 60000, 'label' => '$40k - $60k'],
                        ['min' => 60000, 'max' => null, 'label' => 'Over $60k']
                    ],
                    'statuses' => $statuses
                ];
                break;
            
            case 'catalog.listings.search':
                // Search listings with filters
                $filters = $payload['filters'] ?? [];
                $page = $payload['page'] ?? 1;
                $perPage = $payload['per_page'] ?? 20;
                
                $filtered = array_values($data);
                
                // Apply filters
                if (isset($filters['brand'])) {
                    $filtered = array_filter($filtered, function($item) use ($filters) {
                        return isset($item['brand']) && $item['brand'] === $filters['brand'];
                    });
                }
                
                if (isset($filters['model'])) {
                    $filtered = array_filter($filtered, function($item) use ($filters) {
                        return isset($item['model']) && $item['model'] === $filters['model'];
                    });
                }
                
                if (isset($filters['year'])) {
                    $filtered = array_filter($filtered, function($item) use ($filters) {
                        return isset($item['year']) && (int)$item['year'] === (int)$filters['year'];
                    });
                }
                
                if (isset($filters['min_price'])) {
                    $filtered = array_filter($filtered, function($item) use ($filters) {
                        return isset($item['price']) && (float)$item['price'] >= (float)$filters['min_price'];
                    });
                }
                
                if (isset($filters['max_price'])) {
                    $filtered = array_filter($filtered, function($item) use ($filters) {
                        return isset($item['price']) && (float)$item['price'] <= (float)$filters['max_price'];
                    });
                }
                
                if (isset($filters['status'])) {
                    $filtered = array_filter($filtered, function($item) use ($filters) {
                        return isset($item['status']) && $item['status'] === $filters['status'];
                    });
                }
                
                $totalCount = count($filtered);
                $filtered = array_values($filtered); // Re-index
                
                // Pagination
                $offset = ($page - 1) * $perPage;
                $pagedListings = array_slice($filtered, $offset, $perPage);
                
                $result = [
                    'listings' => $pagedListings,
                    'total_count' => $totalCount,
                    'page' => $page,
                    'per_page' => $perPage,
                    'filters_applied' => $filters
                ];
                break;
            
            case 'catalog.listing.get':
                // Get detailed information about a listing
                $listingId = $payload['id'] ?? '';
                if (!isset($data[$listingId])) {
                    throw new Exception('Listing not found');
                }
                $result = $data[$listingId];
                break;
            
            case 'catalog.photos.list':
                // List photos for a listing
                $listingId = $payload['listing_id'] ?? '';
                
                // For now, return empty photos (can be extended later)
                $result = [
                    'listing_id' => $listingId,
                    'photos' => [],
                    'total_count' => 0
                ];
                break;
            
            case 'catalog.listing.use':
                // Mark listing as used/reserved
                $listingId = $payload['id'] ?? '';
                if (!isset($data[$listingId])) {
                    throw new Exception('Listing not found');
                }
                
                $data[$listingId]['status'] = 'used';
                $data[$listingId]['used_at'] = time();
                $data[$listingId]['used_by'] = $actor['user_id'];
                saveData($data);
                
                $result = [
                    'id' => $listingId,
                    'status' => 'used',
                    'used_at' => $data[$listingId]['used_at'],
                    'used_by' => $data[$listingId]['used_by'],
                    'message' => 'Listing marked as used'
                ];
                break;
                
            default:
                throw new Exception("Unknown capability: $capability");
        }
        
        // Phase 4: Standardized success response
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'data' => $result,
            'trace_id' => $traceId
        ]);
        
    } catch (Exception $e) {
        // Phase 4: Standardized error response
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => [
                'code' => 'ADAPTER_ERROR',
                'message' => $e->getMessage()
            ],
            'trace_id' => $traceId
        ]);
    }
    exit;
}

// Not found
http_response_code(404);
echo json_encode(['error' => 'Not found']);
