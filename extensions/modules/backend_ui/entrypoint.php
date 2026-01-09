<?php
/**
 * Backend UI Entrypoint
 * 
 * Minimal IPC handler for UI backend module.
 * Accepts IPC requests, validates permissions, and routes to authorized modules.
 * 
 * This is a STUB implementation - no real business logic, only contract validation.
 */

declare(strict_types=1);

require_once __DIR__ . '/ipc.php';
require_once __DIR__ . '/permissions.php';

/**
 * Main entry point for IPC commands
 */
function handleIpcCommand(array $envelope): array
{
    // Validate envelope structure
    if (!isset($envelope['command']) || !isset($envelope['payload'])) {
        return createErrorResponse('INVALID_ENVELOPE', 'Missing required fields: command or payload');
    }

    $command = $envelope['command'];
    $payload = $envelope['payload'];

    // Route based on command
    switch ($command) {
        case 'backend.ui.health':
            return handleHealth($payload);
        
        case 'backend.ui.session.open':
            return handleSessionOpen($payload);
        
        case 'backend.ui.invoke':
            return handleInvoke($payload);
        
        default:
            return createErrorResponse('UNKNOWN_COMMAND', "Unknown command: {$command}");
    }
}

/**
 * Health check handler
 */
function handleHealth(array $payload): array
{
    return createSuccessResponse([
        'status' => 'ok',
        'module' => 'backend-ui',
        'timestamp' => time(),
    ]);
}

/**
 * Session open handler (stub)
 */
function handleSessionOpen(array $payload): array
{
    // Minimal stub: generate session ID
    $sessionId = 'sess_' . bin2hex(random_bytes(16));
    
    // In real implementation, would store in state/sessions.json
    // For stub, just return success
    
    return createSuccessResponse([
        'session_id' => $sessionId,
        'expires_at' => time() + 3600,
        'role' => $payload['role'] ?? 'viewer',
    ]);
}

/**
 * Invoke handler (stub)
 * Checks permissions and forwards to module
 */
function handleInvoke(array $payload): array
{
    // Validate required fields
    if (!isset($payload['target_module']) || !isset($payload['target_command'])) {
        return createErrorResponse('INVALID_INVOKE', 'Missing target_module or target_command');
    }

    $targetModule = $payload['target_module'];
    $targetCommand = $payload['target_command'];
    $role = $payload['role'] ?? 'viewer';

    // Check permissions (stub - minimal check)
    if (!checkPermission($role, $targetModule, $targetCommand)) {
        return createErrorResponse('PERMISSION_DENIED', 'Role does not have permission for this command');
    }

    // In real implementation, would invoke module via kernel IPC
    // For stub, return deterministic mock response
    return createSuccessResponse([
        'invoked' => true,
        'target_module' => $targetModule,
        'target_command' => $targetCommand,
        'result' => [
            'status' => 'stub_ok',
            'message' => 'Stub response - module not actually invoked',
        ],
    ]);
}

// Main execution if run directly (supports both CLI and HTTP for flexibility)
// CLI: for testing and standalone execution
// HTTP: for web-based IPC invocation
if (php_sapi_name() === 'cli' || !empty($_SERVER['REQUEST_METHOD'])) {
    // Read IPC input from stdin or HTTP POST
    $input = file_get_contents('php://stdin') ?: file_get_contents('php://input');
    
    if (empty($input)) {
        $response = createErrorResponse('NO_INPUT', 'No IPC envelope received');
    } else {
        $envelope = json_decode($input, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            $response = createErrorResponse('INVALID_JSON', 'Failed to parse IPC envelope');
        } else {
            $response = handleIpcCommand($envelope);
        }
    }
    
    // Output response
    echo json_encode($response, JSON_PRETTY_PRINT);
}
