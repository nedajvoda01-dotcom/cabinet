# Adapters

This directory contains external integrations as untrusted extensions following a uniform interface pattern.

## Adapter Structure

Each adapter must implement three endpoints:

### 1. `/invoke` - Execution Endpoint
Executes the adapter's primary function. Receives input, performs work, returns result.

### 2. `/descriptor` - Metadata Endpoint
Returns adapter metadata: name, version, capabilities, schema.

### 3. `/health` - Health Check Endpoint
Returns adapter health status and readiness.

## Available Adapters

- **parser** - Content parsing and extraction
- **photos** - Photo processing and optimization
- **robot** - External automation and robot control
- **storage** - Storage service integration
- **browser-context** - Browser automation context

## Adapter Rules

1. **No Platform Dependencies** - Adapters cannot import platform code
2. **Shared Contracts Only** - Communication via shared contracts
3. **Stateless** - Adapters should be stateless where possible
4. **Idempotent** - Operations should be idempotent
5. **Fail-Safe** - Must handle failures gracefully

## Fallback Mode

Each adapter includes a fallback implementation that works without external dependencies. This allows the system to run end-to-end in demo mode.

## Development

To develop a new adapter:

1. Create directory: `adapters/your-adapter/`
2. Implement required endpoints: `/invoke`, `/descriptor`, `/health`
3. Add fallback implementation
4. Add to artifacts manifest
5. Document in adapter README

## Testing

Adapters should include:
- Unit tests for business logic
- Integration tests for external dependencies
- Health check tests
- Contract compliance tests
