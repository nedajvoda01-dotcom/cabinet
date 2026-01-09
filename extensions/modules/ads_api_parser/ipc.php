<?php
/**
 * Ads API Parser IPC Handler
 * 
 * Minimal IPC handler for ads API parsing module.
 * This is a STUB - no external API calls, no real CSV processing.
 * Only validates IPC contract and returns deterministic responses.
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
            'module' => 'ads-api-parser',
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
            'module' => 'ads-api-parser',
        ],
    ];
}

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
        case 'ads.health':
            return handleHealth($payload);
        
        case 'ads.parse.listings':
            return handleParseListings($payload);
        
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
        'module' => 'ads-api-parser',
        'timestamp' => time(),
    ]);
}

/**
 * Parse listings handler (stub)
 * 
 * In production: would fetch from external API, parse CSV, etc.
 * For stub: return deterministic mock data
 */
function handleParseListings(array $payload): array
{
    // Validate input
    if (!isset($payload['source'])) {
        return createErrorResponse('INVALID_INPUT', 'Missing required field: source');
    }

    $source = $payload['source'];
    
    // Stub response - deterministic mock data
    // NO external API calls, NO network requests
    $mockListings = [
        [
            'id' => 'stub-001',
            'title' => 'Stub Listing 1',
            'price' => 25000,
            'year' => 2020,
        ],
        [
            'id' => 'stub-002',
            'title' => 'Stub Listing 2',
            'price' => 30000,
            'year' => 2021,
        ],
    ];

    return createSuccessResponse([
        'parsed' => true,
        'source' => $source,
        'count' => count($mockListings),
        'listings' => $mockListings,
        'note' => 'STUB DATA - no real API call made',
    ]);
}

// Main execution if run directly
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
