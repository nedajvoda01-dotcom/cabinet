<?php
/**
 * Platform Entry Point
 * Single HTTP endpoint for all API requests
 * Phase 2-4: Control Plane API, Registry-driven, Unified Adapter Protocol
 */

// Autoload classes (simple PSR-4 style autoloader)
spl_autoload_register(function ($class) {
    $prefix = 'Platform\\';
    $base_dir = __DIR__ . '/../src/';
    
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }
    
    $relative_class = substr($class, $len);
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    
    if (file_exists($file)) {
        require $file;
    }
});

// Load legacy classes (backward compatibility)
require_once __DIR__ . '/../Policy.php';
require_once __DIR__ . '/../Limits.php';
require_once __DIR__ . '/../ResultGate.php';
require_once __DIR__ . '/../Storage.php';

use Platform\Http\Security\Requirements\RouteRequirementsMap;
use Platform\Http\Controllers\VersionController;
use Platform\Http\Controllers\CapabilitiesController;
use Platform\Http\Controllers\InvokeController;
use Platform\Http\Controllers\ReloadRegistryController;
use Platform\Registry\RegistryLoader;
use Platform\Registry\CapabilityRouter;
use Platform\Registry\UiProfileResolver;
use Platform\Adapter\AdapterClient;
use Platform\Adapter\RouterAdapter;
use Platform\Core\CapabilityExecutor;

// Load environment variables from .env if exists
if (file_exists(__DIR__ . '/../../.env')) {
    $envLines = file(__DIR__ . '/../../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($envLines as $line) {
        if (strpos(trim($line), '#') === 0) continue;
        if (strpos($line, '=') === false) continue;
        list($key, $value) = explode('=', $line, 2);
        putenv(trim($key) . '=' . trim($value));
    }
}

// Set error handling
error_reporting(E_ALL);
ini_set('display_errors', '0');

// Get registry path from environment
$registryPath = getenv('REGISTRY_PATH') ?: __DIR__ . '/../../registry';

// Determine dev mode
$devMode = getenv('DEV_MODE') === 'true' || getenv('APP_ENV') === 'development';

// Initialize Phase 3 components - Registry as source of truth
$registryLoader = new RegistryLoader($registryPath, $devMode);
$capabilityRouter = new CapabilityRouter($registryLoader);
$uiProfileResolver = new UiProfileResolver($registryLoader);

// Load configurations from registry
$policyConfig = $registryLoader->getPolicy();
$uiConfig = $registryLoader->getUI();
$capabilitiesConfig = $registryLoader->getCapabilities();

// Initialize legacy components (backward compatibility)
$storage = new Storage(getenv('STORAGE_PATH') ?: '/var/lib/cabinet/storage');
$policy = new Policy($registryPath . '/policy.yaml');
$limits = new Limits($policy, $storage, [
    'default_timeout' => (int)(getenv('DEFAULT_TIMEOUT') ?: 30)
]);

// Phase 6: Pass capabilities config and limits to ResultGate
// Phase 6.3: Add registry path for result profiles
$resultGateConfig = [
    'max_response_size' => (int)(getenv('MAX_RESPONSE_SIZE') ?: 10485760), // 10MB
    'max_array_size' => (int)(getenv('MAX_ARRAY_SIZE') ?: 1000),
    'registry_path' => $registryPath
];
$resultGate = new ResultGate($policy, $capabilitiesConfig, $resultGateConfig);

// Initialize Phase 4 component - Adapter Client
$adapterClient = new AdapterClient();

// MVP Step 2: Create RouterAdapter to bridge old Router interface with new components
$routerAdapter = new RouterAdapter($capabilityRouter, $adapterClient);

// MVP Step 2: Initialize CapabilityExecutor (unified pipeline)
$capabilityExecutor = new CapabilityExecutor(
    $routerAdapter,
    $policy,
    $limits,
    $resultGate,
    $storage,
    $uiConfig,
    $capabilitiesConfig
);

// Initialize Phase 2 component - Route Requirements Map
$routeMap = new RouteRequirementsMap();

// Handle request
header('Content-Type: application/json');

try {
    // Parse request
    $method = $_SERVER['REQUEST_METHOD'];
    $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
    
    // Parse query parameters
    parse_str($_SERVER['QUERY_STRING'] ?? '', $queryParams);
    
    // Parse request body
    $input = file_get_contents('php://input');
    $requestData = json_decode($input, true) ?? [];
    
    // Combine query params with request data
    $params = array_merge($queryParams, $requestData);
    
    // Route to appropriate controller
    if ($method === 'GET' && $path === '/api/version') {
        // Phase 2: Version/Health endpoint
        $controller = new VersionController();
        $response = $controller->handle();
        http_response_code(200);
        echo json_encode($response);
        exit;
    }
    
    if ($method === 'GET' && $path === '/api/capabilities') {
        // Phase 2: Capabilities endpoint
        $controller = new CapabilitiesController($policy, $uiConfig, $capabilitiesConfig);
        $response = $controller->handle($params);
        http_response_code(200);
        echo json_encode($response);
        exit;
    }
    
    if ($method === 'POST' && $path === '/api/invoke') {
        // MVP Step 2: Use CapabilityExecutor directly (no legacy Router)
        // Single code path: InvokeController → CapabilityExecutor
        $controller = new InvokeController($capabilityExecutor);
        
        $response = $controller->handle($requestData, $input);
        http_response_code(200);
        echo json_encode($response);
        exit;
    }
    
    if ($method === 'POST' && $path === '/control/reload-registry') {
        // Phase 3: Reload registry endpoint
        $controller = new ReloadRegistryController($registryLoader, $devMode);
        $response = $controller->handle();
        http_response_code(200);
        echo json_encode($response);
        exit;
    }
    
    // Backward compatibility: handle old /api/* requests without specific routing
    if (strpos($path, '/api/') === 0) {
        // This is for any unhandled /api/* paths
        // Keep backward compatibility with old behavior
        http_response_code(404);
        echo json_encode([
            'error' => 'Endpoint not found',
            'path' => $path,
            'method' => $method,
            'available_endpoints' => [
                'GET /api/version',
                'GET /api/capabilities',
                'POST /api/invoke',
                'POST /control/reload-registry'
            ]
        ]);
        exit;
    }
    
    // Not found
    http_response_code(404);
    echo json_encode(['error' => 'Not found']);
    
} catch (Exception $e) {
    // Log error
    if (isset($storage)) {
        $storage->logAudit([
            'error' => true,
            'message' => $e->getMessage(),
            'trace' => $e->getTraceAsString()
        ]);
    }
    
    // Return error response
    $code = http_response_code() ?: 500;
    if ($code < 400) {
        $code = 500;
    }
    http_response_code($code);
    
    if (isset($resultGate)) {
        echo json_encode($resultGate->transformError($e->getMessage(), $code));
    } else {
        echo json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'code' => $code
        ]);
    }
}
