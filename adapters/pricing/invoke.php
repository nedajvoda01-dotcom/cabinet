<?php
/**
 * Pricing Adapter
 * Handles price calculations and pricing rules
 */

// Simple pricing rules storage
$rulesFile = '/tmp/pricing-rules.json';

function loadRules(): array {
    global $rulesFile;
    if (!file_exists($rulesFile)) {
        // Default rules
        return [
            'base_rate' => 100,
            'year_multiplier' => 0.95, // 5% depreciation per year
            'brand_premium' => [
                'BMW' => 1.5,
                'Mercedes' => 1.6,
                'Toyota' => 1.0,
                'Honda' => 1.0
            ]
        ];
    }
    return json_decode(file_get_contents($rulesFile), true) ?? [];
}

function saveRules(array $rules): void {
    global $rulesFile;
    file_put_contents($rulesFile, json_encode($rules, JSON_PRETTY_PRINT));
}

// Handle request
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Health check
if ($path === '/health') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'adapter' => 'pricing']);
    exit;
}

// Invoke endpoint
if ($path === '/invoke' && $method === 'POST') {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    $capability = $request['capability'] ?? '';
    $payload = $request['payload'] ?? [];
    
    $rules = loadRules();
    $response = [];
    
    try {
        switch ($capability) {
            case 'price.calculate':
                $brand = $payload['brand'] ?? '';
                $year = $payload['year'] ?? date('Y');
                $basePrice = $payload['base_price'] ?? 0;
                
                $currentYear = (int)date('Y');
                $age = $currentYear - $year;
                
                // Calculate depreciation
                $depreciation = pow($rules['year_multiplier'], $age);
                
                // Apply brand premium
                $brandMultiplier = $rules['brand_premium'][$brand] ?? 1.0;
                
                // Calculate final price
                $finalPrice = $basePrice * $depreciation * $brandMultiplier;
                
                $response = [
                    'base_price' => $basePrice,
                    'depreciation' => $depreciation,
                    'brand_multiplier' => $brandMultiplier,
                    'final_price' => round($finalPrice, 2),
                    'age_years' => $age
                ];
                break;
                
            case 'price.rule.create':
                $ruleKey = $payload['key'] ?? '';
                $ruleValue = $payload['value'] ?? null;
                if (!$ruleKey) {
                    throw new Exception('Rule key is required');
                }
                $rules[$ruleKey] = $ruleValue;
                saveRules($rules);
                $response = ['key' => $ruleKey, 'value' => $ruleValue];
                break;
                
            case 'price.rule.list':
                $response = $rules;
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
