<?php
/**
 * Router - Capability to Adapter routing
 * Routes requests based on capability → adapter mapping from registry
 */

class Router {
    private array $adaptersConfig;
    private array $capabilitiesConfig;
    
    public function __construct(string $adaptersPath, string $capabilitiesPath) {
        $this->adaptersConfig = $this->loadConfig($adaptersPath);
        $this->capabilitiesConfig = $this->loadConfig($capabilitiesPath);
    }
    
    private function loadConfig(string $path): array {
        // Support both YAML and JSON
        $jsonPath = str_replace('.yaml', '.json', $path);
        
        if (file_exists($jsonPath)) {
            $content = file_get_contents($jsonPath);
            return json_decode($content, true);
        }
        
        if (!file_exists($path)) {
            throw new Exception("Config not found: $path or $jsonPath");
        }
        
        // Try YAML if available
        if (function_exists('yaml_parse_file')) {
            return yaml_parse_file($path);
        }
        
        throw new Exception("YAML extension not available and JSON config not found");
    }
    
    /**
     * Get adapter for capability
     */
    public function getAdapterForCapability(string $capability): ?array {
        if (!isset($this->capabilitiesConfig['capabilities'][$capability])) {
            return null;
        }
        
        $capabilityConfig = $this->capabilitiesConfig['capabilities'][$capability];
        $adapterId = $capabilityConfig['adapter'];
        
        if (!isset($this->adaptersConfig['adapters'][$adapterId])) {
            return null;
        }
        
        $adapter = $this->adaptersConfig['adapters'][$adapterId];
        $adapter['id'] = $adapterId;
        
        // Replace environment variables in URL
        $adapter['url'] = $this->replaceEnvVars($adapter['url']);
        
        return $adapter;
    }
    
    /**
     * Invoke adapter with payload
     */
    public function invoke(array $adapter, string $capability, array $payload, int $timeout = 30): array {
        $url = $adapter['url'] . '/invoke';
        
        // Phase 4: Use standardized adapter protocol
        $traceId = uniqid('trace_', true);
        $actor = [
            'user_id' => $GLOBALS['current_user_id'] ?? 'anonymous',
            'role' => $GLOBALS['current_role'] ?? 'guest',
            'ui' => $GLOBALS['current_ui'] ?? 'unknown'
        ];
        
        $requestData = [
            'capability' => $capability,
            'payload' => $payload,
            'trace_id' => $traceId,
            'actor' => $actor,
            'timestamp' => time()
        ];
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($requestData));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'X-Capability: ' . $capability,
            'X-Trace-Id: ' . $traceId
        ]);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Adapter request failed: $error");
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Adapter returned error: HTTP $httpCode");
        }
        
        $result = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception("Invalid JSON response from adapter");
        }
        
        // Phase 4: Handle standardized response format
        if (isset($result['ok'])) {
            if (!$result['ok']) {
                $errorMsg = $result['error']['message'] ?? 'Unknown adapter error';
                throw new Exception($errorMsg);
            }
            // Return the data part
            return $result['data'] ?? [];
        }
        
        // Legacy response format (for backward compatibility)
        return $result;
    }
    
    /**
     * Replace environment variables in string
     */
    private function replaceEnvVars(string $str): string {
        return preg_replace_callback('/\$\{([^}]+)\}/', function($matches) {
            $envVar = $matches[1];
            return getenv($envVar) ?: $matches[0];
        }, $str);
    }
    
    /**
     * Check adapter health
     */
    public function checkHealth(array $adapter): bool {
        $url = $adapter['url'] . ($adapter['health_check'] ?? '/health');
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        return $httpCode === 200;
    }
}
