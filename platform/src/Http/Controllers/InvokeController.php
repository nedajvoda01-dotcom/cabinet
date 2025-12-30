<?php
/**
 * Invoke Controller
 * Handles capability invocation through adapters
 * Phase 5: Enhanced with authentication and comprehensive audit logging
 */

namespace Platform\Http\Controllers;

use Platform\Http\Security\Authentication;

class InvokeController {
    private $router;
    private $policy;
    private $limits;
    private $resultGate;
    private $storage;
    private $uiConfig;
    private $authentication;
    
    public function __construct($router, $policy, $limits, $resultGate, $storage, $uiConfig) {
        $this->router = $router;
        $this->policy = $policy;
        $this->limits = $limits;
        $this->resultGate = $resultGate;
        $this->storage = $storage;
        $this->uiConfig = $uiConfig;
        $this->authentication = new Authentication();
    }
    
    /**
     * Invoke a capability through an adapter
     * POST /api/invoke
     * Phase 5: Enhanced with authentication and comprehensive audit
     */
    public function handle(array $requestData, string $input): array {
        // Phase 5.1: Authenticate request (fail-closed by default)
        $authenticatedActor = null;
        try {
            $authenticatedActor = $this->authentication->authenticate();
        } catch (\Exception $e) {
            // Log failed authentication attempt
            $this->storage->logAudit([
                'event' => 'authentication_failed',
                'error' => $e->getMessage(),
                'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
            ]);
            
            http_response_code(401);
            throw $e;
        }
        
        // Extract parameters (prefer authenticated actor over request data)
        $capability = $requestData['capability'] ?? null;
        $payload = $requestData['payload'] ?? [];
        
        // Use authenticated actor info, fallback to request data for backward compatibility
        $ui = $authenticatedActor['ui'] ?? $requestData['ui'] ?? 'public';
        $role = $authenticatedActor['role'] ?? $requestData['role'] ?? 'guest';
        $userId = $authenticatedActor['user_id'] ?? $requestData['user_id'] ?? 'anonymous';
        
        if (!$capability) {
            throw new \Exception('Capability is required');
        }
        
        // Set globals for actor context (Phase 4)
        $GLOBALS['current_user_id'] = $userId;
        $GLOBALS['current_role'] = $role;
        $GLOBALS['current_ui'] = $ui;
        
        // Generate run ID
        $runId = uniqid('run_', true);
        
        // Phase 5.4: Enhanced audit logging - start
        $auditEntry = [
            'run_id' => $runId,
            'event' => 'capability_invocation_start',
            'capability' => $capability,
            'ui' => $ui,
            'user_id' => $userId,
            'role' => $role,
            'authenticated' => $authenticatedActor['authenticated'] ?? false,
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown'
        ];
        $this->storage->logAudit($auditEntry);
        
        $adapter = null;
        $result = null;
        $error = null;
        
        try {
            // 1. Validate UI access to capability (Phase 5.2: deny by default)
            if (!$this->policy->validateUIAccess($ui, $capability, $this->uiConfig)) {
                throw new \Exception("UI '$ui' is not allowed to use capability '$capability'");
            }
            
            // 2. Get scopes for role
            $scopes = $this->policy->getScopesForRole($role);
            
            // 3. Check policy - allow/deny + scopes (Phase 5.2: deny by default)
            if (!$this->policy->isAllowed($capability, $role, $scopes)) {
                throw new \Exception('Access denied by policy');
            }
            
            // 4. Check limits - rate limit (Phase 5.3)
            if (!$this->limits->checkRateLimit($capability, $role, $userId)) {
                http_response_code(429);
                throw new \Exception('Rate limit exceeded');
            }
            
            // 5. Check limits - request size (Phase 5.3)
            $requestSize = strlen($input);
            if (!$this->limits->checkRequestSize($requestSize, $role)) {
                http_response_code(413);
                throw new \Exception('Request too large');
            }
            
            // 6. Route to adapter using router interface
            $adapter = $this->router->getAdapterForCapability($capability);
            if (!$adapter) {
                throw new \Exception("No adapter found for capability '$capability'");
            }
            
            // 7. Invoke adapter with timeout (Phase 5.3)
            $timeout = $this->limits->getTimeout($capability);
            $result = $this->limits->enforceTimeout(function() use ($adapter, $capability, $payload, $timeout) {
                return $this->router->invoke($adapter, $capability, $payload, $timeout);
            }, $timeout);
            
            // 8. Apply ResultGate - filter results (Phase 6)
            if (!$this->resultGate->validate($result)) {
                throw new \Exception('Invalid adapter response');
            }
            
            $filtered = $this->resultGate->filter($result, $capability, $scopes);
            
            // 9. Save run record
            $this->storage->saveRun($runId, [
                'capability' => $capability,
                'adapter' => $adapter['id'],
                'status' => 'success',
                'ui' => $ui,
                'user_id' => $userId
            ]);
            
            // Phase 5.4: Enhanced audit logging - success
            $this->storage->logAudit([
                'run_id' => $runId,
                'event' => 'capability_invocation_success',
                'capability' => $capability,
                'adapter' => $adapter['id'],
                'ui' => $ui,
                'user_id' => $userId,
                'role' => $role,
                'result' => 'ok'
            ]);
            
            // 10. Return response
            return [
                'success' => true,
                'run_id' => $runId,
                'result' => $filtered
            ];
            
        } catch (\Exception $e) {
            // Phase 5.4: Enhanced audit logging - error
            $this->storage->logAudit([
                'run_id' => $runId,
                'event' => 'capability_invocation_error',
                'capability' => $capability,
                'adapter' => $adapter['id'] ?? 'unknown',
                'ui' => $ui,
                'user_id' => $userId,
                'role' => $role,
                'result' => 'error',
                'error' => $e->getMessage()
            ]);
            
            // Re-throw to be handled by main error handler
            throw $e;
        }
    }
}
