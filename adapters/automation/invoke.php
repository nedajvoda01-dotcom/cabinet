<?php
/**
 * Automation Adapter
 * Handles workflow execution and automation
 */

// Workflow storage
$workflowsFile = '/tmp/workflows.json';
$executionsFile = '/tmp/workflow-executions.json';

function loadWorkflows(): array {
    global $workflowsFile;
    if (!file_exists($workflowsFile)) {
        // Default workflows
        return [
            'car_onboarding' => [
                'name' => 'Car Onboarding',
                'steps' => ['validate', 'price', 'store', 'notify'],
                'description' => 'Onboard a new car to the system'
            ],
            'price_update' => [
                'name' => 'Price Update',
                'steps' => ['fetch_cars', 'recalculate', 'update'],
                'description' => 'Update prices for all cars'
            ]
        ];
    }
    return json_decode(file_get_contents($workflowsFile), true) ?? [];
}

function loadExecutions(): array {
    global $executionsFile;
    if (!file_exists($executionsFile)) {
        return [];
    }
    return json_decode(file_get_contents($executionsFile), true) ?? [];
}

function saveExecution(string $id, array $execution): void {
    global $executionsFile;
    $executions = loadExecutions();
    $executions[$id] = $execution;
    file_put_contents($executionsFile, json_encode($executions, JSON_PRETTY_PRINT));
}

// Handle request
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Health check
if ($path === '/health') {
    http_response_code(200);
    echo json_encode(['status' => 'ok', 'adapter' => 'automation']);
    exit;
}

// Invoke endpoint
if ($path === '/invoke' && $method === 'POST') {
    $input = file_get_contents('php://input');
    $request = json_decode($input, true);
    
    // Phase 4: Standardized adapter protocol
    $capability = $request['capability'] ?? '';
    $payload = $request['payload'] ?? [];
    $traceId = $request['trace_id'] ?? uniqid('trace_');
    $actor = $request['actor'] ?? ['user_id' => 'unknown', 'role' => 'guest', 'ui' => 'unknown'];
    
    $workflows = loadWorkflows();
    
    try {
        switch ($capability) {
            case 'workflow.execute':
                $workflowId = $payload['workflow_id'] ?? '';
                if (!isset($workflows[$workflowId])) {
                    throw new Exception('Workflow not found');
                }
                
                $executionId = uniqid('exec_');
                $execution = [
                    'id' => $executionId,
                    'workflow_id' => $workflowId,
                    'status' => 'running',
                    'started_at' => time(),
                    'started_by' => $actor['user_id'],
                    'steps_completed' => 0,
                    'total_steps' => count($workflows[$workflowId]['steps']),
                    'trace_id' => $traceId
                ];
                
                saveExecution($executionId, $execution);
                
                $result = [
                    'execution_id' => $executionId,
                    'workflow_id' => $workflowId,
                    'status' => 'running'
                ];
                break;
                
            case 'workflow.status':
                $executionId = $payload['execution_id'] ?? '';
                $executions = loadExecutions();
                
                if (!isset($executions[$executionId])) {
                    throw new Exception('Execution not found');
                }
                
                // Simulate progress
                $execution = $executions[$executionId];
                if ($execution['status'] === 'running') {
                    $execution['steps_completed'] = min(
                        $execution['steps_completed'] + 1,
                        $execution['total_steps']
                    );
                    
                    if ($execution['steps_completed'] >= $execution['total_steps']) {
                        $execution['status'] = 'completed';
                        $execution['completed_at'] = time();
                    }
                    
                    saveExecution($executionId, $execution);
                }
                
                $result = $execution;
                break;
                
            case 'workflow.list':
                $result = $workflows;
                break;
                
            default:
                throw new Exception("Unknown capability: $capability");
        }
        
        // Phase 4: Standardized success response
        http_response_code(200);
        echo json_encode([
            'ok' => true,
            'data' => $result,
            'trace_id' => $traceId
        ]);
        
    } catch (Exception $e) {
        // Phase 4: Standardized error response
        http_response_code(400);
        echo json_encode([
            'ok' => false,
            'error' => [
                'code' => 'ADAPTER_ERROR',
                'message' => $e->getMessage()
            ],
            'trace_id' => $traceId
        ]);
    }
    exit;
}

// Not found
http_response_code(404);
echo json_encode(['error' => 'Not found']);
