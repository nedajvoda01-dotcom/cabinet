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
