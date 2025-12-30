<?php
/**
 * Capability Router
 * Routes capability to adapter using registry
 * Phase 3: Registry as source of truth
 */

namespace Platform\Registry;

class CapabilityRouter {
    private RegistryLoader $registryLoader;
    
    public function __construct(RegistryLoader $registryLoader) {
        $this->registryLoader = $registryLoader;
    }
    
    /**
     * Get adapter ID for capability
     */
    public function getAdapterId(string $capability): ?string {
        $capabilities = $this->registryLoader->getCapabilities();
        
        if (!isset($capabilities['capabilities'][$capability])) {
            return null;
        }
        
        return $capabilities['capabilities'][$capability]['adapter'] ?? null;
    }
    
    /**
     * Get adapter configuration for capability
     */
    public function getAdapter(string $capability): ?array {
        $adapterId = $this->getAdapterId($capability);
        
        if (!$adapterId) {
            return null;
        }
        
        return $this->getAdapterById($adapterId);
    }
    
    /**
     * Get adapter by ID
     */
    public function getAdapterById(string $adapterId): ?array {
        $adapters = $this->registryLoader->getAdapters();
        
        if (!isset($adapters['adapters'][$adapterId])) {
            return null;
        }
        
        $adapter = $adapters['adapters'][$adapterId];
        $adapter['id'] = $adapterId;
        
        // Replace environment variables in URL
        $adapter['url'] = $this->replaceEnvVars($adapter['url']);
        
        return $adapter;
    }
    
    /**
     * Get all capabilities
     */
    public function getAllCapabilities(): array {
        $capabilities = $this->registryLoader->getCapabilities();
        return $capabilities['capabilities'] ?? [];
    }
    
    /**
     * Get capability info
     */
    public function getCapabilityInfo(string $capability): ?array {
        $capabilities = $this->getAllCapabilities();
        return $capabilities[$capability] ?? null;
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
}
