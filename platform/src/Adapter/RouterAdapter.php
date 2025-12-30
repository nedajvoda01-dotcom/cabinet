<?php
/**
 * Router Adapter
 * Adapter pattern to make CapabilityRouter + AdapterClient compatible with legacy Router interface
 * MVP Step 2: Bridge between old Router interface and new components
 */

namespace Platform\Adapter;

use Platform\Registry\CapabilityRouter;

class RouterAdapter {
    private CapabilityRouter $capabilityRouter;
    private AdapterClient $adapterClient;
    
    public function __construct(CapabilityRouter $capabilityRouter, AdapterClient $adapterClient) {
        $this->capabilityRouter = $capabilityRouter;
        $this->adapterClient = $adapterClient;
    }
    
    /**
     * Get adapter for capability
     * Compatible with legacy Router::getAdapterForCapability()
     */
    public function getAdapterForCapability(string $capability): ?array {
        return $this->capabilityRouter->getAdapter($capability);
    }
    
    /**
     * Invoke adapter with payload
     * Compatible with legacy Router::invoke()
     */
    public function invoke(array $adapter, string $capability, array $payload, int $timeout = 30): array {
        $traceId = uniqid('trace_', true);
        $actor = [
            'user_id' => $GLOBALS['current_user_id'] ?? 'anonymous',
            'role' => $GLOBALS['current_role'] ?? 'guest',
            'ui' => $GLOBALS['current_ui'] ?? 'unknown'
        ];
        
        $response = $this->adapterClient->invoke(
            $adapter,
            $capability,
            $payload,
            $traceId,
            $actor,
            $timeout
        );
        
        // Handle standardized response format
        if (!$response['ok']) {
            $errorMsg = $response['error']['message'] ?? 'Unknown adapter error';
            throw new \Exception($errorMsg);
        }
        
        // Return the data part (compatible with legacy Router)
        return $response['data'] ?? [];
    }
    
    /**
     * Check adapter health
     * Compatible with legacy Router::checkHealth()
     */
    public function checkHealth(array $adapter): bool {
        return $this->adapterClient->checkHealth($adapter);
    }
}
