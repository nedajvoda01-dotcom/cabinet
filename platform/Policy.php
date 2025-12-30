<?php
/**
 * Policy - Access control and scopes
 * Checks allow/deny rules based on roles and scopes
 */

class Policy {
    private array $config;
    
    public function __construct(string $configPath) {
        $this->config = $this->loadConfig($configPath);
    }
    
    private function loadConfig(string $path): array {
        if (!file_exists($path)) {
            throw new Exception("Policy config not found: $path");
        }
        
        return yaml_parse_file($path);
    }
    
    /**
     * Check if request is allowed based on role and capability
     */
    public function isAllowed(string $capability, string $role, array $scopes = []): bool {
        // Check if capability has specific policy
        if (isset($this->config['capability_policies'][$capability])) {
            $policy = $this->config['capability_policies'][$capability];
            $requiredScopes = $policy['required_scopes'] ?? [];
            
            // Check if user has required scopes
            foreach ($requiredScopes as $requiredScope) {
                if (!in_array($requiredScope, $scopes)) {
                    return false;
                }
            }
        }
        
        return true;
    }
    
    /**
     * Get rate limit for role and capability
     */
    public function getRateLimit(string $capability, string $role): int {
        // Capability-specific limit
        if (isset($this->config['capability_policies'][$capability]['rate_limit'])) {
            return $this->config['capability_policies'][$capability]['rate_limit'];
        }
        
        // Role-specific limit
        if (isset($this->config['roles'][$role]['rate_limit'])) {
            return $this->config['roles'][$role]['rate_limit'];
        }
        
        // Default limit
        return $this->config['global']['default_rate_limit'] ?? 100;
    }
    
    /**
     * Get max request size for role
     */
    public function getMaxRequestSize(string $role): int {
        if (isset($this->config['roles'][$role]['max_request_size'])) {
            return $this->config['roles'][$role]['max_request_size'];
        }
        
        return $this->config['global']['max_request_size'] ?? 10485760;
    }
    
    /**
     * Get scopes for role
     */
    public function getScopesForRole(string $role): array {
        return $this->config['roles'][$role]['scopes'] ?? [];
    }
    
    /**
     * Validate UI access to capability
     */
    public function validateUIAccess(string $ui, string $capability, array $uiConfig): bool {
        if (!isset($uiConfig['ui'][$ui])) {
            return false;
        }
        
        $allowedCapabilities = $uiConfig['ui'][$ui]['allowed_capabilities'] ?? [];
        return in_array($capability, $allowedCapabilities);
    }
}
