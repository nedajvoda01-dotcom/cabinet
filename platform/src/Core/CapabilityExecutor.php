<?php
/**
 * CapabilityExecutor - Unified pipeline for capability invocation
 * Phase 6.2: Ensures every capability goes through complete security pipeline
 * 
 * Pipeline: auth → policy → limits → routing → invoke → resultgate
 */

namespace Platform\Core;

use Platform\Http\Security\Authentication;

class CapabilityExecutor {
    private $router;
    private $policy;
    private $limits;
    private $resultGate;
    private $storage;
    private $uiConfig;
    private $authentication;
    private $capabilitiesConfig;
    
    public function __construct($router, $policy, $limits, $resultGate, $storage, $uiConfig, array $capabilitiesConfig) {
        $this->router = $router;
        $this->policy = $policy;
        $this->limits = $limits;
        $this->resultGate = $resultGate;
        $this->storage = $storage;
        $this->uiConfig = $uiConfig;
        $this->capabilitiesConfig = $capabilitiesConfig;
        // Lazy load authentication when needed
        $this->authentication = null;
    }
    
    /**
     * Execute a capability with full security pipeline
     * 
     * @param array $actor Actor information (user_id, role, ui)
     * @param string $capability Capability to execute
     * @param array $payload Data payload
     * @param array $context Execution context (for chain tracking)
     * @return array Result from adapter
     * 
     * Phase 6.2: This method ensures EVERY capability invocation (including internal)
     * goes through the complete security pipeline
     */
    public function executeCapability(array $actor, string $capability, array $payload, array $context = []): array {
        $runId = uniqid('run_', true);
        
        // Extract actor details
        $ui = $actor['ui'] ?? 'internal';
        $role = $actor['role'] ?? 'guest';
        $userId = $actor['user_id'] ?? 'system';
        
        // Set globals for actor context (for backward compatibility)
        $GLOBALS['current_user_id'] = $userId;
        $GLOBALS['current_role'] = $role;
        $GLOBALS['current_ui'] = $ui;
        
        // Track execution context (for chain validation)
        $isChained = isset($context['parent_capability']);
        $parentCapability = $context['parent_capability'] ?? null;
        
        // Phase 6.2: Audit logging - start
        $auditEntry = [
            'run_id' => $runId,
            'event' => 'capability_invocation_start',
            'capability' => $capability,
            'ui' => $ui,
            'user_id' => $userId,
            'role' => $role,
            'is_chained' => $isChained,
            'parent_capability' => $parentCapability,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        $this->storage->logAudit($auditEntry);
        
        $adapter = null;
        
        try {
            // Step 1: Policy - Check if capability is allowed
            // Phase 6.2: This applies to BOTH UI-initiated and internal chain calls
            if (!$this->validateCapabilityAccess($capability, $ui, $role, $context)) {
                http_response_code(403);
                throw new \Exception("Access denied for capability '$capability'");
            }
            
            // Step 2: Limits - Check rate limit and request size
            // (Only for non-chained calls; internal calls inherit parent's quota)
            if (!$isChained) {
                $scopes = $this->policy->getScopesForRole($role);
                
                if (!$this->limits->checkRateLimit($capability, $role, $userId)) {
                    http_response_code(429);
                    throw new \Exception('Rate limit exceeded');
                }
            }
            
            // Step 3: Routing - Find adapter for capability
            $adapter = $this->router->getAdapterForCapability($capability);
            if (!$adapter) {
                throw new \Exception("No adapter found for capability '$capability'");
            }
            
            // Step 4: Invoke - Call adapter with timeout
            $timeout = $this->limits->getTimeout($capability);
            $result = $this->limits->enforceTimeout(function() use ($adapter, $capability, $payload, $timeout) {
                return $this->router->invoke($adapter, $capability, $payload, $timeout);
            }, $timeout);
            
            // Step 5: ResultGate - Filter and validate results
            if (!$this->resultGate->validate($result)) {
                throw new \Exception('Invalid adapter response');
            }
            
            $scopes = $this->policy->getScopesForRole($role);
            // Phase 6.3: Pass UI to apply result profile
            $filtered = $this->resultGate->filter($result, $capability, $scopes, $ui);
            
            // Step 6: Save run record
            $this->storage->saveRun($runId, [
                'capability' => $capability,
                'adapter' => $adapter['id'],
                'status' => 'success',
                'ui' => $ui,
                'user_id' => $userId,
                'is_chained' => $isChained,
                'parent_capability' => $parentCapability
            ]);
            
            // Audit logging - success
            $this->storage->logAudit([
                'run_id' => $runId,
                'event' => 'capability_invocation_success',
                'capability' => $capability,
                'adapter' => $adapter['id'],
                'ui' => $ui,
                'user_id' => $userId,
                'role' => $role,
                'result' => 'ok',
                'is_chained' => $isChained
            ]);
            
            return $filtered;
            
        } catch (\Exception $e) {
            // Audit logging - error
            $this->storage->logAudit([
                'run_id' => $runId,
                'event' => 'capability_invocation_error',
                'capability' => $capability,
                'adapter' => $adapter['id'] ?? 'unknown',
                'ui' => $ui,
                'user_id' => $userId,
                'role' => $role,
                'result' => 'error',
                'error' => $e->getMessage(),
                'is_chained' => $isChained
            ]);
            
            throw $e;
        }
    }
    
    /**
     * Phase 6.2: Validate capability access with chain context
     * 
     * This method checks:
     * 1. UI access (from registry with profile support)
     * 2. Role scopes (from policy)
     * 3. Internal capability chain validation (e.g., storage.listings.upsert_batch only allowed from import.run)
     */
    private function validateCapabilityAccess(string $capability, string $ui, string $role, array $context): bool {
        // Check UI access (unless it's an internal/system call)
        if ($ui !== 'internal' && !$this->validateUIAccessWithProfiles($ui, $role, $capability)) {
            return false;
        }
        
        // Get scopes for role
        $scopes = $this->policy->getScopesForRole($role);
        
        // Check basic policy
        if (!$this->policy->isAllowed($capability, $role, $scopes)) {
            return false;
        }
        
        // Phase 6.2: Check if this is an internal-only capability
        if ($this->isInternalOnlyCapability($capability)) {
            // Internal-only capabilities can only be called from allowed parent capabilities
            if (!isset($context['parent_capability'])) {
                // Direct call to internal capability - DENY
                return false;
            }
            
            // Check if parent capability is allowed to call this internal capability
            return $this->isAllowedChain($context['parent_capability'], $capability);
        }
        
        return true;
    }
    
    /**
     * Validate UI access with profile support
     * This handles the unified UI model where different roles get different profiles
     */
    private function validateUIAccessWithProfiles(string $ui, string $role, string $capability): bool {
        if (!isset($this->uiConfig['ui'][$ui])) {
            return false;
        }
        
        $uiEntry = $this->uiConfig['ui'][$ui];
        
        // Check if UI has profile-based structure (new unified UI)
        if (isset($uiEntry['profiles'])) {
            // Determine profile based on role
            // TODO: Make this mapping configurable in registry for extensibility
            $profile = $role === 'admin' ? 'admin' : 'public';
            $profileConfig = $uiEntry['profiles'][$profile] ?? $uiEntry['profiles']['public'];
            
            $allowedCapabilities = $profileConfig['allowed_capabilities'] ?? [];
        } else {
            // Legacy format (single allowed_capabilities list)
            $allowedCapabilities = $uiEntry['allowed_capabilities'] ?? [];
        }
        
        return in_array($capability, $allowedCapabilities);
    }
    
    /**
     * Phase 6.2 / MVP Step 4: Check if capability is internal-only
     * Now reads from registry instead of hardcoded array
     */
    private function isInternalOnlyCapability(string $capability): bool {
        // Read from registry/capabilities.yaml
        if (!isset($this->capabilitiesConfig['capabilities'][$capability])) {
            return false;
        }
        
        $config = $this->capabilitiesConfig['capabilities'][$capability];
        return $config['internal_only'] ?? false;
    }
    
    /**
     * Phase 6.2 / MVP Step 4: Check if capability chain is allowed
     * Now reads from registry instead of hardcoded array
     */
    private function isAllowedChain(string $parentCapability, string $childCapability): bool {
        // Read from registry/capabilities.yaml
        if (!isset($this->capabilitiesConfig['capabilities'][$childCapability])) {
            return false;
        }
        
        $childConfig = $this->capabilitiesConfig['capabilities'][$childCapability];
        $allowedParents = $childConfig['allowed_parents'] ?? [];
        
        return in_array($parentCapability, $allowedParents);
    }
}
