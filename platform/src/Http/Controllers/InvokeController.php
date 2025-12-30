<?php
/**
 * Invoke Controller
 * Handles capability invocation through adapters
 * Phase 5: Enhanced with authentication and comprehensive audit logging
 */

namespace Platform\Http\Controllers;

use Platform\Http\Security\Authentication;
use Platform\Core\CapabilityExecutor;

class InvokeController {
    private $executor;
    private $authentication;
    
    public function __construct(CapabilityExecutor $executor) {
        $this->executor = $executor;
        $this->authentication = new Authentication();
    }
    
    /**
     * Invoke a capability through an adapter
     * POST /api/invoke
     * MVP Step 2: Refactored to use CapabilityExecutor (unified pipeline)
     */
    public function handle(array $requestData, string $input): array {
        // Phase 5.1: Authenticate request (fail-closed by default)
        try {
            $authenticatedActor = $this->authentication->authenticate();
        } catch (\Exception $e) {
            http_response_code(401);
            throw $e;
        }
        
        // Extract parameters
        $capability = $requestData['capability'] ?? null;
        $payload = $requestData['payload'] ?? [];
        
        // Use authenticated actor info, fallback to request data for backward compatibility
        $ui = $authenticatedActor['ui'] ?? $requestData['ui'] ?? 'public';
        $role = $authenticatedActor['role'] ?? $requestData['role'] ?? 'guest';
        $userId = $authenticatedActor['user_id'] ?? $requestData['user_id'] ?? 'anonymous';
        
        if (!$capability) {
            throw new \Exception('Capability is required');
        }
        
        // Build actor for CapabilityExecutor
        $actor = [
            'user_id' => $userId,
            'role' => $role,
            'ui' => $ui,
            'authenticated' => $authenticatedActor['authenticated'] ?? false
        ];
        
        // Execute through unified pipeline
        // This ensures: auth → policy → limits → routing → invoke → resultgate
        $filtered = $this->executor->executeCapability($actor, $capability, $payload);
        
        // Generate run ID for response
        $runId = uniqid('run_', true);
        
        // Return response
        return [
            'success' => true,
            'run_id' => $runId,
            'result' => $filtered
        ];
    }
}
