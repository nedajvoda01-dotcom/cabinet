# Phase 2-4 Implementation Guide

This document describes the implementation of Phases 2-4 of the Cabinet Platform enhancement.

## Phase 2: Thin Control Plane API

### New Endpoints

#### 1. GET /api/version
Returns platform version and health status.

**Example:**
```bash
curl http://localhost:8080/api/version
```

**Response:**
```json
{
  "version": "1.0.0",
  "status": "healthy",
  "timestamp": 1767086227,
  "platform": "Cabinet",
  "phase": "Phase 2 - Control Plane API"
}
```

#### 2. GET /api/capabilities
Returns capabilities filtered by UI and policy.

**Parameters:**
- `ui` - UI identifier (e.g., "public", "admin")
- `role` - User role (e.g., "guest", "admin")

**Example:**
```bash
curl "http://localhost:8080/api/capabilities?ui=public&role=guest"
```

**Response:**
```json
{
  "ui": "public",
  "role": "guest",
  "scopes": ["read"],
  "capabilities": [
    {
      "name": "car.read",
      "description": "Read car information",
      "adapter": "car-storage"
    }
  ],
  "count": 4
}
```

#### 3. POST /api/invoke
Main endpoint for invoking capabilities (enhanced with Phase 4 protocol).

**Request:**
```json
{
  "capability": "car.create",
  "payload": {
    "brand": "Toyota",
    "model": "Camry"
  },
  "ui": "admin",
  "role": "admin",
  "user_id": "admin_user"
}
```

### RouteRequirementsMap

New component that defines routes and their security requirements:
- Location: `platform/src/Http/Security/Requirements/RouteRequirementsMap.php`
- Defines authentication and authorization requirements for each endpoint
- Supports admin-only endpoints

## Phase 3: Registry as Source of Truth

### New Components

#### 1. RegistryLoader
Reads and caches registry YAML files with support for hot reloading in dev mode.

**Location:** `platform/src/Registry/RegistryLoader.php`

**Features:**
- Supports both YAML and JSON formats
- Caching in production mode
- Hot reload in dev mode
- Methods: `getAdapters()`, `getCapabilities()`, `getUI()`, `getPolicy()`

#### 2. CapabilityRouter
Routes capabilities to adapters using registry configuration.

**Location:** `platform/src/Registry/CapabilityRouter.php`

**Methods:**
- `getAdapterId(capability)` - Get adapter ID for a capability
- `getAdapter(capability)` - Get full adapter configuration
- `getAllCapabilities()` - Get all registered capabilities

#### 3. UiProfileResolver
Resolves UI profiles and their allowed capabilities.

**Location:** `platform/src/Registry/UiProfileResolver.php`

**Methods:**
- `getProfile(uiId)` - Get UI profile configuration
- `getAllowedCapabilities(uiId)` - Get capabilities allowed for UI
- `isCapabilityAllowed(uiId, capability)` - Check if UI can use capability
- `getFilteredCapabilities(uiId, role, policy)` - Get filtered capabilities by policy

### Hot Reload Endpoint

#### POST /control/reload-registry
Reloads registry configuration without restarting (dev mode only).

**Example:**
```bash
curl -X POST http://localhost:8080/control/reload-registry
```

**Response:**
```json
{
  "success": true,
  "message": "Registry reloaded successfully",
  "timestamp": 1767086227
}
```

**Note:** Only works when `DEV_MODE=true` or `APP_ENV=development` is set.

## Phase 4: Standardized Adapter Protocol

### Adapter Request Protocol

All adapters now receive requests in a standardized format:

```json
{
  "capability": "car.create",
  "payload": { ... },
  "trace_id": "trace_6953986ac5c463",
  "actor": {
    "user_id": "admin_user",
    "role": "admin",
    "ui": "admin"
  },
  "timestamp": 1767086186
}
```

### Adapter Response Protocol

Adapters return standardized responses:

**Success:**
```json
{
  "ok": true,
  "data": { ... },
  "trace_id": "trace_6953986ac5c463"
}
```

**Error:**
```json
{
  "ok": false,
  "error": {
    "code": "ADAPTER_ERROR",
    "message": "Error description"
  },
  "trace_id": "trace_6953986ac5c463"
}
```

### Actor Tracking

Adapters now track who performed actions:
- `created_by` - Who created the record
- `updated_by` - Who updated the record  
- `started_by` - Who started the workflow
- `calculated_by` - Who requested the calculation

### AdapterClient

New HTTP client for invoking adapters with standardized protocol.

**Location:** `platform/src/Adapter/AdapterClient.php`

**Methods:**
- `invoke(adapter, capability, payload, traceId, actor, timeout)` - Invoke adapter
- `checkHealth(adapter, timeout)` - Check adapter health

## Configuration

### Environment Variables

New environment variables added:

```bash
# Development mode (enables hot reload)
DEV_MODE=true
APP_ENV=development

# Existing variables
PLATFORM_PORT=8080
REGISTRY_PATH=/app/registry
STORAGE_PATH=/var/lib/cabinet/storage
```

### Docker Compose Updates

Added Apache configuration mount and dev mode environment:

```yaml
volumes:
  - ./platform/apache-config.conf:/etc/apache2/sites-available/000-default.conf
environment:
  - DEV_MODE=${DEV_MODE:-true}
  - APP_ENV=${APP_ENV:-development}
```

## Directory Structure

```
platform/
├── public/
│   └── index.php              # New entry point
├── src/
│   ├── Http/
│   │   ├── Controllers/
│   │   │   ├── VersionController.php
│   │   │   ├── CapabilitiesController.php
│   │   │   ├── InvokeController.php
│   │   │   └── ReloadRegistryController.php
│   │   └── Security/
│   │       └── Requirements/
│   │           └── RouteRequirementsMap.php
│   ├── Registry/
│   │   ├── RegistryLoader.php
│   │   ├── CapabilityRouter.php
│   │   └── UiProfileResolver.php
│   └── Adapter/
│       └── AdapterClient.php
├── apache-config.conf         # Apache virtual host config
└── index.php                  # Backward compatibility wrapper
```

## Testing

All existing smoke tests pass with the new implementation:

```bash
cd tests
./run-smoke-tests.sh
```

**Results:** 9/9 tests passing

### Test New Endpoints

```bash
# Test version endpoint
curl http://localhost:8080/api/version

# Test capabilities endpoint
curl "http://localhost:8080/api/capabilities?ui=admin&role=admin"

# Test reload endpoint
curl -X POST http://localhost:8080/control/reload-registry

# Test invoke with Phase 4 protocol
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -d '{
    "capability": "car.list",
    "payload": {},
    "ui": "public",
    "role": "guest",
    "user_id": "test_user"
  }'
```

## Backward Compatibility

All changes are backward compatible:
- Old `platform/index.php` delegates to new `platform/public/index.php`
- Existing Router class updated to support Phase 4 protocol
- Legacy response format still supported (automatically wrapped)
- All existing tests pass without modification

## Benefits

1. **Phase 2:**
   - Clear API structure with explicit endpoints
   - Better separation of concerns
   - Easier to test and document

2. **Phase 3:**
   - Registry as single source of truth
   - Hot reload in development
   - Cleaner architecture with dedicated components

3. **Phase 4:**
   - Standardized adapter protocol
   - Request tracing with trace_id
   - Actor tracking for audit trails
   - Consistent error handling

## Future Enhancements

- Full migration to CapabilityRouter (Phase 3 component)
- Additional control plane endpoints
- Enhanced monitoring and metrics
- GraphQL API support
