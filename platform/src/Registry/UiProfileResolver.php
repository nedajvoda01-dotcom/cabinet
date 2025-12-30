<?php
/**
 * UI Profile Resolver
 * Resolves UI profiles and allowed capabilities
 * Phase 3: Registry as source of truth
 */

namespace Platform\Registry;

class UiProfileResolver {
    private RegistryLoader $registryLoader;
    
    public function __construct(RegistryLoader $registryLoader) {
        $this->registryLoader = $registryLoader;
    }
    
    /**
     * Get UI profile
     */
    public function getProfile(string $uiId): ?array {
        $ui = $this->registryLoader->getUI();
        return $ui['ui'][$uiId] ?? null;
    }
    
    /**
     * Get allowed capabilities for UI
     */
    public function getAllowedCapabilities(string $uiId): array {
        $profile = $this->getProfile($uiId);
        return $profile['allowed_capabilities'] ?? [];
    }
    
    /**
     * Get scopes for UI
     */
    public function getScopes(string $uiId): array {
        $profile = $this->getProfile($uiId);
        return $profile['scopes'] ?? [];
    }
    
    /**
     * Check if UI is allowed to use capability
     */
    public function isCapabilityAllowed(string $uiId, string $capability): bool {
        $allowedCapabilities = $this->getAllowedCapabilities($uiId);
        return in_array($capability, $allowedCapabilities);
    }
    
    /**
     * Get filtered capabilities for UI (considering policy)
     */
    public function getFilteredCapabilities(string $uiId, string $role, $policy): array {
        $allowedCapabilities = $this->getAllowedCapabilities($uiId);
        $scopes = $policy->getScopesForRole($role);
        
        $capabilityRouter = new CapabilityRouter($this->registryLoader);
        $allCapabilities = $capabilityRouter->getAllCapabilities();
        
        $filtered = [];
        foreach ($allowedCapabilities as $capabilityName) {
            // Check if capability exists
            if (!isset($allCapabilities[$capabilityName])) {
                continue;
            }
            
            // Check if role has permission based on policy
            if (!$policy->isAllowed($capabilityName, $role, $scopes)) {
                continue;
            }
            
            $capInfo = $allCapabilities[$capabilityName];
            $filtered[$capabilityName] = [
                'name' => $capabilityName,
                'description' => $capInfo['description'] ?? '',
                'adapter' => $capInfo['adapter'] ?? ''
            ];
        }
        
        return $filtered;
    }
    
    /**
     * Get all UI profiles
     */
    public function getAllProfiles(): array {
        $ui = $this->registryLoader->getUI();
        return $ui['ui'] ?? [];
    }
}
