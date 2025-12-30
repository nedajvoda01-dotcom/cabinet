#!/bin/bash
# Create a new adapter scaffold
# Usage: ./scripts/new-adapter.sh <adapter-name>

set -e

ADAPTER_NAME=$1

if [ -z "$ADAPTER_NAME" ]; then
    echo "Usage: ./scripts/new-adapter.sh <adapter-name>"
    echo "Example: ./scripts/new-adapter.sh my-service"
    exit 1
fi

# Validate adapter name (lowercase, dashes allowed)
if ! echo "$ADAPTER_NAME" | grep -qE '^[a-z][a-z0-9-]*$'; then
    echo "Error: Adapter name must start with a letter and contain only lowercase letters, numbers, and dashes"
    exit 1
fi

ADAPTER_DIR="./adapters/$ADAPTER_NAME"

if [ -d "$ADAPTER_DIR" ]; then
    echo "Error: Adapter directory already exists: $ADAPTER_DIR"
    exit 1
fi

echo "Creating new adapter: $ADAPTER_NAME"
echo "=================================="

# Create adapter directory structure
mkdir -p "$ADAPTER_DIR"

# Create invoke.php
cat > "$ADAPTER_DIR/invoke.php" << 'EOF'
<?php
/**
 * Adapter Invoke Endpoint
 * Handles capability invocation for this adapter
 */

// Enable error reporting for development
ini_set('display_errors', '1');
error_reporting(E_ALL);

header('Content-Type: application/json');

// Health check endpoint
if ($_SERVER['REQUEST_METHOD'] === 'GET' && $_SERVER['REQUEST_URI'] === '/health') {
    echo json_encode([
        'status' => 'healthy',
        'adapter' => 'ADAPTER_NAME',
        'timestamp' => time()
    ]);
    exit;
}

// Only accept POST requests for capability invocation
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed. Use POST for capability invocation.']);
    exit;
}

// Parse request
$input = file_get_contents('php://input');
$requestData = json_decode($input, true);

if (json_last_error() !== JSON_ERROR_NONE) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid JSON']);
    exit;
}

$capability = $requestData['capability'] ?? null;
$payload = $requestData['payload'] ?? [];

if (!$capability) {
    http_response_code(400);
    echo json_encode(['error' => 'Capability is required']);
    exit;
}

// Capability routing
try {
    switch ($capability) {
        case 'ADAPTER_NAME.example':
            // Example capability handler
            $result = handleExample($payload);
            break;
            
        default:
            throw new Exception("Unknown capability: $capability");
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'result' => $result
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'error' => $e->getMessage()
    ]);
}

/**
 * Example capability handler
 */
function handleExample(array $payload): array {
    // TODO: Implement your capability logic here
    return [
        'message' => 'Example capability executed',
        'payload_received' => $payload,
        'timestamp' => time()
    ];
}
EOF

# Replace ADAPTER_NAME placeholder
sed -i "s/ADAPTER_NAME/$ADAPTER_NAME/g" "$ADAPTER_DIR/invoke.php"

# Create README.md
cat > "$ADAPTER_DIR/README.md" << EOF
# $ADAPTER_NAME Adapter

## Description

TODO: Add adapter description here

## Capabilities

TODO: List capabilities provided by this adapter

- \`$ADAPTER_NAME.example\` - Example capability

## Configuration

TODO: Document any configuration requirements

## Development

### Testing locally

\`\`\`bash
# Start the adapter
docker-compose up $ADAPTER_NAME
\`\`\`

### Health check

\`\`\`bash
curl http://localhost:PORT/health
\`\`\`

## Integration

This adapter is registered in:
- \`registry/adapters.yaml\` - Adapter configuration
- \`registry/capabilities.yaml\` - Capability mappings
EOF

echo ""
echo "✓ Created adapter directory: $ADAPTER_DIR"
echo "✓ Created invoke.php"
echo "✓ Created README.md"
echo ""
echo "Next steps:"
echo "==========="
echo "1. Edit $ADAPTER_DIR/invoke.php to implement your capabilities"
echo "2. Register the adapter in registry/adapters.yaml:"
echo ""
echo "   adapters:"
echo "     $ADAPTER_NAME:"
echo "       url: http://adapter-$ADAPTER_NAME"
echo "       timeout: 30"
echo "       description: \"TODO: Add description\""
echo ""
echo "3. Add service to docker-compose.yml:"
echo ""
echo "   adapter-$ADAPTER_NAME:"
echo "     image: php:8.2-apache"
echo "     container_name: cabinet-adapter-$ADAPTER_NAME"
echo "     networks:"
echo "       - mesh"
echo "     expose:"
echo "       - \"80\""
echo "     volumes:"
echo "       - ./adapters/$ADAPTER_NAME:/var/www/html"
echo "     command: >"
echo "       bash -c \""
echo "         a2enmod rewrite &&"
echo "         apache2-foreground"
echo "       \""
echo ""
echo "4. Add capabilities using: ./scripts/new-capability.sh $ADAPTER_NAME.your-capability $ADAPTER_NAME"
echo ""
echo "5. Validate registry: ./scripts/validate-registry.sh"
echo ""
echo "6. Restart services: docker-compose restart platform"
