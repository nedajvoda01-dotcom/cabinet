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
    
    $capability = $request['capability'] ?? '';
    $payload = $request['payload'] ?? [];
    
    $data = loadData();
    $response = [];
    
    try {
        switch ($capability) {
            case 'car.create':
                $carId = uniqid('car_');
                $car = $payload;
                $car['id'] = $carId;
                $car['created_at'] = time();
                $data[$carId] = $car;
                saveData($data);
                $response = ['id' => $carId, 'car' => $car];
                break;
                
            case 'car.read':
                $carId = $payload['id'] ?? '';
                if (!isset($data[$carId])) {
                    throw new Exception('Car not found');
                }
                $response = $data[$carId];
                break;
                
            case 'car.update':
                $carId = $payload['id'] ?? '';
                if (!isset($data[$carId])) {
                    throw new Exception('Car not found');
                }
                $data[$carId] = array_merge($data[$carId], $payload);
                $data[$carId]['updated_at'] = time();
                saveData($data);
                $response = $data[$carId];
                break;
                
            case 'car.delete':
                $carId = $payload['id'] ?? '';
                if (!isset($data[$carId])) {
                    throw new Exception('Car not found');
                }
                unset($data[$carId]);
                saveData($data);
                $response = ['deleted' => true, 'id' => $carId];
                break;
                
            case 'car.list':
                $response = array_values($data);
                break;
                
            default:
                throw new Exception("Unknown capability: $capability");
        }
        
        http_response_code(200);
        echo json_encode($response);
        
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode(['error' => $e->getMessage()]);
    }
    exit;
}

// Not found
http_response_code(404);
echo json_encode(['error' => 'Not found']);
