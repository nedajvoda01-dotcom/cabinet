# Phase 6 Implementation Guide (Steps 6.1-6.4)

This document describes the implementation of Phase 6, which includes network isolation, unified capability execution pipeline, result profiles, and import idempotency.

## Overview

Phase 6 introduces the following security and operational improvements:

1. **Network Isolation (Step 6.1)** - Docker network segmentation to isolate adapters
2. **Policy on Every Step (Step 6.2)** - Unified capability execution pipeline with chain validation
3. **Result Profiles (Step 6.3)** - UI-specific field filtering
4. **Import Idempotency (Step 6.4)** - Duplicate-free CSV imports

## Step 6.1: Network Isolation

### Goal
Physically isolate adapters so that:
- UI (browser) can only see the platform
- Platform can see all adapters
- Adapters cannot communicate with each other, UI, or the internet (unless explicitly allowed)

### Implementation

#### Docker Networks

Two networks are defined in `docker-compose.yml`:

1. **edge** (public network)
   - Platform (exposed on port 8080)
   - UI containers (admin, public)
   
2. **mesh** (private network)
   - Platform (bridge to adapters)
   - All adapters (car-storage, pricing, automation)

#### Network Configuration

```yaml
networks:
  edge:
    driver: bridge
  mesh:
    driver: bridge
    internal: false  # Allow outbound if needed
```

#### Service Configuration

**Platform** - Connected to both networks:
```yaml
platform:
  networks:
    - edge
    - mesh
  ports:
    - "${PLATFORM_PORT:-8080}:80"
```

**Adapters** - Connected only to mesh, no published ports:
```yaml
adapter-car-storage:
  networks:
    - mesh
  expose:
    - "80"  # Internal only
  # NO ports: section
```

**UI** - Connected only to edge:
```yaml
ui-admin:
  networks:
    - edge
  ports:
    - "${UI_ADMIN_PORT:-3000}:80"
```

### Testing

Run the network isolation test:

```bash
cd tests
./test-network-isolation.sh
```

**Expected Results:**
- ✓ Platform can reach adapters
- ✓ Adapters cannot reach each other
- ✓ Adapters cannot reach UI
- ✓ UI can reach platform
- ✓ UI cannot reach adapters directly
- ✓ Adapters have no published ports
- ✓ Host cannot reach adapters on old ports

### Security Benefits

1. **Adapter Isolation** - Adapters cannot attack each other
2. **UI Isolation** - UI cannot bypass platform to reach adapters
3. **No External Exposure** - Adapters are completely internal
4. **Defense in Depth** - Multiple layers of network segmentation

## Step 6.2: Policy on Every Step

### Goal
Ensure that NO capability is invoked without proper authorization - even internal chain steps.

### Implementation

#### CapabilityExecutor

New unified pipeline in `platform/src/Core/CapabilityExecutor.php`:

```php
class CapabilityExecutor {
    public function executeCapability(
        array $actor, 
        string $capability, 
        array $payload, 
        array $context = []
    ): array
```

**Pipeline:**
1. **Authentication** - Verify actor identity
2. **Policy Check** - Validate capability access
3. **Limits Check** - Rate limits, request size
4. **Routing** - Find adapter for capability
5. **Invoke** - Call adapter with timeout
6. **ResultGate** - Filter and validate results
7. **Audit** - Log the operation

#### Internal Capability Protection

Internal-only capabilities that cannot be called directly from UI:
- `storage.listings.upsert_batch`
- `storage.imports.register`
- `storage.imports.mark_done`
- `parser.calculate_hash`

#### Capability Chains

Allowed capability chains are explicitly defined:

```php
$allowedChains = [
    'import.run' => [
        'parser.calculate_hash',
        'storage.imports.register',
        'storage.listings.upsert_batch',
        'storage.imports.mark_done',
    ],
];
```

Direct calls to internal capabilities return HTTP 403.

### Configuration

**Policy** (`registry/policy.yaml`):
```yaml
capability_policies:
  storage.imports.register:
    required_scopes:
      - admin  # Blocks UI access
    rate_limit: 100
```

**UI Registry** (`registry/ui.yaml`):
```yaml
ui:
  admin:
    allowed_capabilities:
      - import.run  # Public capability
      # NOT storage.imports.register (internal)
```

### Testing

Run the capability chain test:

```bash
php tests/test-capability-chains.php
```

**Expected Results:**
- ✓ Direct call to internal capability fails (403)
- ✓ Chained call from authorized parent succeeds
- ✓ Chained call from unauthorized parent fails
- ✓ Allowed chains correctly defined
- ✓ Public capabilities can be called directly

### Security Benefits

1. **No Bypass** - Internal capabilities protected even if policy is misconfigured
2. **Chain Validation** - Only specific chains are allowed
3. **Audit Trail** - All calls logged with parent context
4. **Fail-Closed** - Unknown chains are denied by default

## Step 6.3: Result Profiles

### Goal
Different UIs see only the fields appropriate for their profile, even if storage has all fields.

### Implementation

#### Result Profile Configuration

New file: `registry/result_profiles.yaml`

```yaml
profiles:
  internal_ui:
    name: "Internal Admin UI"
    max_response_size: 10485760  # 10MB
    max_array_size: 5000
    fields:
      listing:
        - id
        - brand
        - cost_price      # Admin-only
        - profit_margin   # Admin-only
        - internal_notes  # Admin-only
        # ... all fields
        
  public_ui:
    name: "Public UI"
    max_response_size: 1048576  # 1MB
    max_array_size: 100
    fields:
      listing:
        - id
        - brand
        - model
        - price
        # NO cost_price, profit_margin, internal_notes
```

#### UI to Profile Mapping

```yaml
ui_profiles:
  admin: internal_ui
  internal: internal_ui
  public: public_ui
  operations: ops_ui
```

#### ResultGate Enhancement

The `ResultGate` class now:
1. Loads result profiles from registry
2. Maps UI ID to profile
3. Applies profile-specific field filtering
4. Uses profile-specific limits (max size, max array)

**Precedence:**
- If result profile exists → use profile (skip capability allowlist)
- If no result profile → fall back to capability allowlist

### Testing

Run the result profiles test:

```bash
php tests/test-result-profiles.php
```

**Expected Results:**
- ✓ Admin UI sees all fields (internal_ui profile)
- ✓ Public UI sees only public fields
- ✓ Operations UI sees operational fields but not financial
- ✓ Result profiles affect array size limits
- ✓ UI profile mapping works correctly

### Security Benefits

1. **Field-Level Control** - Granular control over what each UI sees
2. **Data Minimization** - UIs only get fields they need
3. **Profile-Based Limits** - Different resource limits per profile
4. **Future-Proof** - Easy to add new profiles without code changes

## Step 6.4: Import Idempotency

### Goal
If you accidentally import the same CSV twice, data should not duplicate.

### Implementation

#### Import Tracking

New methods in `Storage` class:

```php
// Register import attempt (checks for duplicates)
public function registerImport(
    string $contentHash, 
    string $filename, 
    string $source = 'inbox'
): array

// Mark import as completed
public function markImportDone(
    string $contentHash, 
    array $stats = []
): void
```

**Import Status:**
- `new` - First time seeing this file
- `duplicate` - File already imported successfully
- `in_progress` - Import currently running
- `done` - Import completed
- `failed` - Import failed

#### Deduplication Strategy

1. **Content Hash** - SHA256 of CSV content
2. **Import Registry** - Track imports by hash
3. **External ID** - Each listing has stable external_id
4. **Upsert Logic** - Create or update by external_id

#### Upsert Implementation

```php
// Single upsert
public function upsertListing(array $listing): array

// Batch upsert (efficient)
public function upsertListingsBatch(array $listings): array
```

**Upsert Logic:**
- If external_id exists → update (preserve created_at, created_by)
- If external_id new → create (set created_at, created_by)

#### Import Workflow

1. **Calculate Hash** - SHA256 of CSV content
2. **Register Import** - Check if hash already imported
3. **Parse CSV** - Extract rows
4. **Batch Upsert** - Insert/update listings
5. **Mark Done** - Record success with stats

### New Capabilities

**Public Capabilities:**
- `import.run` - Run import workflow (admin only)

**Internal Capabilities:**
- `storage.imports.register` - Register import (internal only)
- `storage.imports.mark_done` - Mark completion (internal only)
- `storage.listings.upsert_batch` - Batch upsert (internal only)

### Adapter Implementation

The `car-storage` adapter implements:
- `import.run` - Full import workflow
- `storage.imports.register` - Import registration
- `storage.imports.mark_done` - Import completion
- `storage.listings.upsert_batch` - Batch upsert

### Testing

Run the import idempotency test:

```bash
php tests/test-import-idempotency.php
```

**Expected Results:**
- ✓ First import registered as 'new'
- ✓ Import can be marked as completed
- ✓ Duplicate import detected
- ✓ Different content registered as new
- ✓ Listing created on first upsert
- ✓ Listing updated on second upsert
- ✓ Batch upsert works correctly
- ✓ Upsert requires external_id

### Security Benefits

1. **Idempotency** - Safe to retry imports
2. **No Duplication** - Same file won't create duplicates
3. **Audit Trail** - All imports tracked
4. **Consistent State** - Upsert ensures data consistency

## Running All Tests

### Unit Tests

```bash
# Capability chain enforcement
php tests/test-capability-chains.php

# Result profile filtering
php tests/test-result-profiles.php

# Import idempotency
php tests/test-import-idempotency.php
```

### Network Tests

```bash
# Network isolation (requires Docker)
cd tests
./test-network-isolation.sh
```

### Integration Tests

```bash
# Full smoke tests
cd tests
./run-smoke-tests.sh
```

## Configuration Files

### New Files
- `registry/result_profiles.yaml` - Result profile definitions
- `platform/src/Core/CapabilityExecutor.php` - Unified execution pipeline
- `tests/test-network-isolation.sh` - Network isolation tests
- `tests/test-capability-chains.php` - Capability chain tests
- `tests/test-result-profiles.php` - Result profile tests
- `tests/test-import-idempotency.php` - Import idempotency tests

### Modified Files
- `docker-compose.yml` - Added network segmentation
- `platform/ResultGate.php` - Added result profile support
- `platform/Storage.php` - Added import tracking and upsert methods
- `registry/capabilities.yaml` - Added import capabilities
- `registry/policy.yaml` - Added import policies
- `registry/ui.yaml` - Added import.run to admin UI
- `adapters/car-storage/invoke.php` - Added import capabilities

## API Examples

### Import CSV

```bash
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin_secret_key_12345" \
  -d '{
    "capability": "import.run",
    "payload": {
      "filename": "cars.csv",
      "csv_data": "external_id,brand,model,year,price\nEXT001,Toyota,Camry,2020,25000"
    }
  }'
```

**First Import Response:**
```json
{
  "success": true,
  "result": {
    "data": {
      "import_id": "import_abc123",
      "status": "completed",
      "records_created": 1,
      "records_updated": 0,
      "records_failed": 0
    }
  }
}
```

**Duplicate Import Response:**
```json
{
  "success": true,
  "result": {
    "data": {
      "import_id": "import_abc123",
      "status": "duplicate",
      "message": "File already imported",
      "records_created": 0,
      "records_updated": 0,
      "records_failed": 0
    }
  }
}
```

### Test Internal Capability Protection

Try to call internal capability directly (should fail):

```bash
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin_secret_key_12345" \
  -d '{
    "capability": "storage.listings.upsert_batch",
    "payload": {
      "listings": []
    }
  }'
```

**Response:**
```json
{
  "error": true,
  "message": "Access denied for capability 'storage.listings.upsert_batch'",
  "code": 403
}
```

## Summary

Phase 6 implementation provides:

1. **Network Isolation** - Adapters are completely isolated from each other and from external access
2. **Policy Enforcement** - Every capability invocation goes through security pipeline, including internal chains
3. **Field Filtering** - Different UIs see different fields based on result profiles
4. **Import Safety** - CSV imports are idempotent and prevent duplicates

All features are tested and documented. The implementation follows the principle of defense in depth with multiple layers of security controls.
