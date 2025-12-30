<?php
/**
 * Capabilities Controller
 * Handles capabilities listing filtered by UI and policy
 */

namespace Platform\Http\Controllers;

use Platform\Http\Security\Authentication;

class CapabilitiesController {
    private $policy;
    private $uiConfig;
    private $capabilitiesConfig;
    private $authentication;
    
    public function __construct($policy, $uiConfig, $capabilitiesConfig) {
        $this->policy = $policy;
        $this->uiConfig = $uiConfig;
        $this->capabilitiesConfig = $capabilitiesConfig;
        $this->authentication = new Authentication();
    }
    
    /**
     * Get capabilities filtered by UI and policy
     * GET /api/capabilities?ui=cabinet
     * Role is determined from authentication context, NOT from query params
     */
    public function handle(array $params): array {
        // Authenticate request to get role from server context
        try {
            $authenticatedActor = $this->authentication->authenticate();
        } catch (\Exception $e) {
            // If auth fails, default to guest/public (unauthenticated)
            $authenticatedActor = [
                'authenticated' => false,
                'user_id' => 'anonymous',
                'role' => 'guest',
                'ui' => 'cabinet'
            ];
        }
        
        // UI comes from query param (or authenticated context)
        $ui = $params['ui'] ?? $authenticatedActor['ui'] ?? 'cabinet';
        
        // Role is ALWAYS determined server-side from authentication, NEVER from client
        $role = $authenticatedActor['role'];
        
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
