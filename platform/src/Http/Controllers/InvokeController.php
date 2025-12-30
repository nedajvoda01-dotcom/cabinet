<?php
/**
 * Invoke Controller
 * Handles capability invocation through adapters
 */

namespace Platform\Http\Controllers;

class InvokeController {
    private $router;
    private $policy;
    private $limits;
    private $resultGate;
    private $storage;
    private $uiConfig;
    
    public function __construct($router, $policy, $limits, $resultGate, $storage, $uiConfig) {
        $this->router = $router;
        $this->policy = $policy;
        $this->limits = $limits;
        $this->resultGate = $resultGate;
        $this->storage = $storage;
        $this->uiConfig = $uiConfig;
    }
    
    /**
     * Invoke a capability through an adapter
     * POST /api/invoke
     */
    public function handle(array $requestData, string $input): array {
        // Extract parameters
        $capability = $requestData['capability'] ?? null;
        $payload = $requestData['payload'] ?? [];
        $ui = $requestData['ui'] ?? 'public';
        $role = $requestData['role'] ?? 'guest';
        $userId = $requestData['user_id'] ?? 'anonymous';
        
        if (!$capability) {
            throw new \Exception('Capability is required');
        }
        
        // Generate run ID
        $runId = uniqid('run_', true);
        
        // Log audit entry
        $this->storage->logAudit([
            'run_id' => $runId,
            'capability' => $capability,
            'ui' => $ui,
            'user_id' => $userId,
            'role' => $role
        ]);
        
        // 1. Validate UI access to capability
        if (!$this->policy->validateUIAccess($ui, $capability, $this->uiConfig)) {
            throw new \Exception("UI '$ui' is not allowed to use capability '$capability'");
        }
        
        // 2. Get scopes for role
        $scopes = $this->policy->getScopesForRole($role);
        
        // 3. Check policy - allow/deny + scopes
        if (!$this->policy->isAllowed($capability, $role, $scopes)) {
            throw new \Exception('Access denied by policy');
        }
        
        // 4. Check limits - rate limit
        if (!$this->limits->checkRateLimit($capability, $role, $userId)) {
            http_response_code(429);
            throw new \Exception('Rate limit exceeded');
        }
        
        // 5. Check limits - request size
        $requestSize = strlen($input);
        if (!$this->limits->checkRequestSize($requestSize, $role)) {
            http_response_code(413);
            throw new \Exception('Request too large');
        }
        
        // 6. Route to adapter using router interface
        // The router can be either old Router or new CapabilityRouter wrapped
        $adapter = $this->router->getAdapterForCapability($capability);
        if (!$adapter) {
            throw new \Exception("No adapter found for capability '$capability'");
        }
        
        // 7. Invoke adapter with timeout
        $timeout = $this->limits->getTimeout($capability);
        $result = $this->limits->enforceTimeout(function() use ($adapter, $capability, $payload, $timeout) {
            return $this->router->invoke($adapter, $capability, $payload, $timeout);
        }, $timeout);
        
        // 8. Apply ResultGate - filter results
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
        
        // 10. Return response
        return [
            'success' => true,
            'run_id' => $runId,
            'result' => $filtered
        ];
    }
}
