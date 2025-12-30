<?php
/**
 * Capabilities Controller
 * Handles capabilities listing filtered by UI and policy
 */

namespace Platform\Http\Controllers;

class CapabilitiesController {
    private $policy;
    private $uiConfig;
    private $capabilitiesConfig;
    
    public function __construct($policy, $uiConfig, $capabilitiesConfig) {
        $this->policy = $policy;
        $this->uiConfig = $uiConfig;
        $this->capabilitiesConfig = $capabilitiesConfig;
    }
    
    /**
     * Get capabilities filtered by UI and policy
     * GET /api/capabilities?ui=admin
     */
    public function handle(array $params): array {
        $ui = $params['ui'] ?? 'public';
        $role = $params['role'] ?? 'guest';
        
        // Get all capabilities from registry
        $allCapabilities = $this->capabilitiesConfig['capabilities'] ?? [];
        
        // Get allowed capabilities for UI
        $allowedCapabilities = $this->uiConfig['ui'][$ui]['allowed_capabilities'] ?? [];
        
        // Get scopes for role
        $scopes = $this->policy->getScopesForRole($role);
        
        // Filter capabilities
        $filtered = [];
        foreach ($allCapabilities as $capabilityName => $capabilityInfo) {
            // Check if UI is allowed to use this capability
            if (!in_array($capabilityName, $allowedCapabilities)) {
                continue;
            }
            
            // Check if role has permission based on policy
            if (!$this->policy->isAllowed($capabilityName, $role, $scopes)) {
                continue;
            }
            
            $filtered[$capabilityName] = [
                'name' => $capabilityName,
                'description' => $capabilityInfo['description'] ?? '',
                'adapter' => $capabilityInfo['adapter'] ?? ''
            ];
        }
        
        return [
            'ui' => $ui,
            'role' => $role,
            'scopes' => $scopes,
            'capabilities' => array_values($filtered),
            'count' => count($filtered)
        ];
    }
}
