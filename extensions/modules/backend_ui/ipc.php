<?php
/**
 * Backend UI IPC Adapter
 * 
 * Provides IPC helper functions for encoding/decoding messages.
 * Follows the contract defined in shared/contracts/v1/envelope.schema.yaml
 */

declare(strict_types=1);

/**
 * Create a success response envelope
 */
function createSuccessResponse(array $data): array
{
    return [
        'result' => [
            'ok' => true,
            'data' => $data,
        ],
        'metadata' => [
            'timestamp' => time(),
            'module' => 'backend-ui',
        ],
    ];
}

/**
 * Create an error response envelope
 */
function createErrorResponse(string $errorCode, string $message, array $details = []): array
{
    return [
        'result' => [
            'ok' => false,
            'error' => [
                'code' => $errorCode,
                'message' => $message,
                'details' => $details,
            ],
        ],
        'metadata' => [
            'timestamp' => time(),
            'module' => 'backend-ui',
        ],
    ];
}

/**
 * Validate IPC envelope structure
 */
function validateEnvelope(array $envelope): bool
{
    // Basic validation per contract
    if (!isset($envelope['command'])) {
        return false;
    }
    
    if (!isset($envelope['payload'])) {
        return false;
    }
    
    return true;
}

/**
 * Parse IPC command string into parts
 */
function parseCommand(string $command): array
{
    $parts = explode('.', $command);
    
    return [
        'namespace' => $parts[0] ?? '',
        'module' => $parts[1] ?? '',
        'action' => $parts[2] ?? '',
    ];
}
