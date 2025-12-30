<?php
/**
 * Authentication - API Key and JWT validation
 * Phase 5.1: MVP authentication with "fail-closed" approach
 */

namespace Platform\Http\Security;

class Authentication {
    private array $config;
    
    public function __construct(array $config = []) {
        $this->config = $config;
    }
    
    /**
     * Authenticate request using X-API-Key header
     * Returns actor information if authenticated, throws exception otherwise
     * 
     * @throws \Exception if authentication fails
     */
    public function authenticate(): array {
        // Check if authentication is enabled
        $enableAuth = getenv('ENABLE_AUTH') === 'true';
        
        if (!$enableAuth) {
            // In dev mode without auth, return default actor
            return [
                'authenticated' => false,
                'user_id' => 'anonymous',
                'role' => 'guest',
                'ui' => 'unknown'
            ];
        }
        
        // Get X-API-Key header
        $apiKey = $this->getApiKeyFromHeaders();
        
        if (!$apiKey) {
            throw new \Exception('Authentication required: X-API-Key header missing');
        }
        
        // Validate API key and get actor information
        $actor = $this->validateApiKey($apiKey);
        
        if (!$actor) {
            throw new \Exception('Authentication failed: Invalid API key');
        }
        
        return $actor;
    }
    
    /**
     * Get API key from request headers
     */
    private function getApiKeyFromHeaders(): ?string {
        // Check X-API-Key header (case insensitive)
        
        // In CLI/test mode, check $_SERVER directly
        if (php_sapi_name() === 'cli' || !function_exists('getallheaders')) {
            foreach ($_SERVER as $name => $value) {
                if (strtolower($name) === 'http_x_api_key') {
                    return $value;
                }
            }
            return null;
        }
        
        // In web context, use getallheaders
        $headers = getallheaders();
        if (!$headers) {
            return null;
        }
        
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'x-api-key') {
                return $value;
            }
        }
        
        return null;
    }
    
    /**
     * Validate API key and return actor information
     * 
     * API keys format: <ui>_<role>_<secret>
     * Example: admin_admin_secret123
     */
    private function validateApiKey(string $apiKey): ?array {
        // Get configured API keys from environment
        $configuredKeys = $this->loadApiKeys();
        
        if (!isset($configuredKeys[$apiKey])) {
            return null;
        }
        
        $keyConfig = $configuredKeys[$apiKey];
        
        return [
            'authenticated' => true,
            'user_id' => $keyConfig['user_id'] ?? 'api_user',
            'role' => $keyConfig['role'],
            'ui' => $keyConfig['ui']
        ];
    }
    
    /**
     * Load API keys from environment variables or configuration
     * 
     * Expected format in .env:
     * API_KEY_ADMIN=admin_admin_secret123
     * API_KEY_PUBLIC=public_guest_secret456
     */
    private function loadApiKeys(): array {
        $keys = [];
        
        // Load from environment variables
        // API_KEY_ADMIN format: ui|role|user_id
        $envPrefix = 'API_KEY_';
        
        foreach ($_ENV as $key => $value) {
            if (strpos($key, $envPrefix) === 0) {
                $keyName = substr($key, strlen($envPrefix));
                $parts = explode('|', $value);
                
                if (count($parts) >= 2) {
                    $apiKey = $parts[0];
                    $keys[$apiKey] = [
                        'ui' => $parts[1] ?? 'unknown',
                        'role' => $parts[2] ?? 'guest',
                        'user_id' => $parts[3] ?? strtolower($keyName) . '_user'
                    ];
                }
            }
        }
        
        // Also check getenv for Docker compatibility
        if (empty($keys)) {
            // Default admin key for MVP
            $adminKey = getenv('API_KEY_ADMIN');
            if ($adminKey) {
                $parts = explode('|', $adminKey);
                $apiKey = $parts[0];
                $keys[$apiKey] = [
                    'ui' => $parts[1] ?? 'admin',
                    'role' => $parts[2] ?? 'admin',
                    'user_id' => $parts[3] ?? 'admin_user'
                ];
            }
            
            // Default public key for MVP
            $publicKey = getenv('API_KEY_PUBLIC');
            if ($publicKey) {
                $parts = explode('|', $publicKey);
                $apiKey = $parts[0];
                $keys[$apiKey] = [
                    'ui' => $parts[1] ?? 'public',
                    'role' => $parts[2] ?? 'guest',
                    'user_id' => $parts[3] ?? 'public_user'
                ];
            }
        }
        
        return $keys;
    }
    
    /**
     * Check if request is authenticated
     */
    public function isAuthenticated(): bool {
        try {
            $this->authenticate();
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }
}
