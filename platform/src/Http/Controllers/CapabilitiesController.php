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
     * GET /api/capabilities?ui=cabinet&role=admin
     */
    public function handle(array $params): array {
        $ui = $params['ui'] ?? 'cabinet';
        $role = $params['role'] ?? 'guest';
        
        // Get UI configuration
        $uiEntry = $this->uiConfig['ui'][$ui] ?? null;
        if (!$uiEntry) {
            // Fallback to old config format for backwards compatibility
            $allowedCapabilities = [];
            $uiProfile = 'public';
        } else {
            // New unified UI with profiles
            if (isset($uiEntry['profiles'])) {
                // Determine profile based on role
                $profile = $role === 'admin' ? 'admin' : 'public';
                $profileConfig = $uiEntry['profiles'][$profile] ?? $uiEntry['profiles']['public'];
                
                $allowedCapabilities = $profileConfig['allowed_capabilities'] ?? [];
                $uiProfile = $profileConfig['ui_profile'] ?? $profile;
            } else {
                // Legacy format
                $allowedCapabilities = $uiEntry['allowed_capabilities'] ?? [];
                $uiProfile = $ui;
            }
        }
        
        // Get all capabilities from registry
        $allCapabilities = $this->capabilitiesConfig['capabilities'] ?? [];
        
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
            'ui_profile' => $uiProfile,
            'scopes' => $scopes,
            'capabilities' => array_values($filtered),
            'count' => count($filtered)
        ];
    }
}
