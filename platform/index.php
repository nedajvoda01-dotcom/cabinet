<?php
/**
 * Platform Entry Point
 * Single HTTP endpoint for all API requests (/api/*)
 */

// Load dependencies
require_once __DIR__ . '/Router.php';
require_once __DIR__ . '/Policy.php';
require_once __DIR__ . '/Limits.php';
require_once __DIR__ . '/ResultGate.php';
require_once __DIR__ . '/Storage.php';

// Load environment variables from .env if exists
if (file_exists(__DIR__ . '/../.env')) {
    $envLines = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Initialize components
$storage = new Storage(getenv('STORAGE_PATH') ?: '/var/lib/cabinet/storage');
$policy = new Policy(__DIR__ . '/../registry/policy.yaml');
$uiConfig = yaml_parse_file(__DIR__ . '/../registry/ui.yaml');
$router = new Router(
    __DIR__ . '/../registry/adapters.yaml',
    __DIR__ . '/../registry/capabilities.yaml'
);

$limits = new Limits($policy, $storage, [
    'default_timeout' => (int)(getenv('DEFAULT_TIMEOUT') ?: 30)
]);
$resultGate = new ResultGate($policy);

// Handle request
header('Content-Type: application/json');

try {
    // Parse request
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Only handle /api/* requests
    if (strpos($path, '/api/') !== 0) {
        http_response_code(404);
        echo json_encode(['error' => 'Not found']);
        exit;
    }
    
    // Parse request body
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true) ?? [];
    
    // Extract parameters
    $capability = $requestData['capability'] ?? null;
    $payload = $requestData['payload'] ?? [];
    $ui = $requestData['ui'] ?? 'public';
    $role = $requestData['role'] ?? 'guest';
    $userId = $requestData['user_id'] ?? 'anonymous';
    
    if (!$capability) {
        throw new Exception('Capability is required');
    }
    
    // Generate run ID
    $runId = uniqid('run_', true);
    
    // Log audit entry
    $storage->logAudit([
        'run_id' => $runId,
        'capability' => $capability,
        'ui' => $ui,
        'user_id' => $userId,
        'role' => $role,
        'method' => $method,
        'path' => $path
    ]);
    
    // 1. Validate UI access to capability
    if (!$policy->validateUIAccess($ui, $capability, $uiConfig)) {
        throw new Exception("UI '$ui' is not allowed to use capability '$capability'");
    }
    
    // 2. Get scopes for role
    $scopes = $policy->getScopesForRole($role);
    
    // 3. Check policy - allow/deny + scopes
    if (!$policy->isAllowed($capability, $role, $scopes)) {
        throw new Exception('Access denied by policy');
    }
    
    // 4. Check limits - rate limit
    if (!$limits->checkRateLimit($capability, $role, $userId)) {
        http_response_code(429);
        throw new Exception('Rate limit exceeded');
    }
    
    // 5. Check limits - request size
    $requestSize = strlen($input);
    if (!$limits->checkRequestSize($requestSize, $role)) {
        http_response_code(413);
        throw new Exception('Request too large');
    }
    
    // 6. Route to adapter
    $adapter = $router->getAdapterForCapability($capability);
    if (!$adapter) {
        throw new Exception("No adapter found for capability '$capability'");
    }
    
    // 7. Invoke adapter with timeout
    $timeout = $limits->getTimeout($capability);
    $result = $limits->enforceTimeout(function() use ($router, $adapter, $capability, $payload, $timeout) {
        return $router->invoke($adapter, $capability, $payload, $timeout);
    }, $timeout);
    
    // 8. Apply ResultGate - filter results
    if (!$resultGate->validate($result)) {
        throw new Exception('Invalid adapter response');
    }
    
    $filtered = $resultGate->filter($result, $capability, $scopes);
    
    // 9. Save run record
    $storage->saveRun($runId, [
        'capability' => $capability,
        'adapter' => $adapter['id'],
        'status' => 'success',
        'ui' => $ui,
        'user_id' => $userId
    ]);
    
    // 10. Return response
    http_response_code(200);
    echo json_encode([
        'success' => true,
        'run_id' => $runId,
        'result' => $filtered
    ]);
    
} catch (Exception $e) {
    // Log error
    $storage->logAudit([
        'error' => true,
        'message' => $e->getMessage(),
        'trace' => $e->getTraceAsString()
    ]);
    
    // Return error response
    $code = http_response_code() ?: 500;
    http_response_code($code);
    echo json_encode($resultGate->transformError($e->getMessage(), $code));
}
