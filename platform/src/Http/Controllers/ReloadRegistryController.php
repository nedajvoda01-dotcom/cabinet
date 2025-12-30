<?php
/**
 * Reload Registry Controller
 * Handles hot reload of registry configuration (dev mode)
 */

namespace Platform\Http\Controllers;

class ReloadRegistryController {
    private $registryLoader;
    private $devMode;
    
    public function __construct($registryLoader, bool $devMode = false) {
        $this->registryLoader = $registryLoader;
        $this->devMode = $devMode;
    }
    
    /**
     * Reload registry configuration
     * POST /control/reload-registry
     */
    public function handle(): array {
        // Only allow in dev mode
        if (!$this->devMode) {
            http_response_code(403);
            throw new \Exception('Registry reload is only available in dev mode');
        }
        
        // Reload registry
        $this->registryLoader->reload();
        
        return [
            'success' => true,
            'message' => 'Registry reloaded successfully',
            'timestamp' => time()
        ];
    }
}
