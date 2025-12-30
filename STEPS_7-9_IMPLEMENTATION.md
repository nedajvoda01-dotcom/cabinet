# Steps 7-9 Implementation Summary

## Overview

This document describes the implementation of Steps 7-9, which add catalog search capabilities, ensure import orchestration through the core, and provide comprehensive smoke tests.

## Step 7: Import Orchestration Through Core

### Goal
Ensure that import.run orchestrates calls through the core platform, with no direct HTTP calls from the parser.

### Implementation

#### New Capabilities
- **parser.parse_csv** (internal-only): Parses CSV data and normalizes listings
  - Input: csv_data, filename
  - Output: listings[], photos[], report, errors[]
  - Location: `adapters/car-storage/invoke.php`

#### Orchestration Flow
1. UI calls `import.run` capability
2. `import.run` in adapter:
   - Calculates content hash for idempotency
   - Checks if file already imported
   - Parses CSV locally (no HTTP calls)
   - Uses storage.listings.upsert_batch (internal capability)
   - Marks import as done
3. All network calls are made by the core platform, not by adapters

#### Security
- `parser.parse_csv` is marked as internal-only in CapabilityExecutor
- Can only be called from authorized parent capability (import.run)
- Direct calls from UI return 403 Forbidden

### Files Modified
- `platform/src/Core/CapabilityExecutor.php`: Added parser.parse_csv to internal capabilities list
- `adapters/car-storage/invoke.php`: Implemented parser.parse_csv handler
- `registry/capabilities.yaml`: Defined parser.parse_csv capability

## Step 8: Catalog Search UI

### Goal
Connect the UI search screen to catalog capabilities that work through the core platform.

### New Capabilities

#### 1. catalog.filters.get
**Purpose**: Get available filter options for search
**Input**: None
**Output**:
```json
{
  "brands": ["Toyota", "Honda", ...],
  "models": ["Camry", "Accord", ...],
  "years": [2020, 2021, 2022, ...],
  "price_ranges": [
    {"min": 0, "max": 20000, "label": "Under $20k"},
    ...
  ],
  "statuses": ["available", "sold", "reserved", ...]
}
```

#### 2. catalog.listings.search
**Purpose**: Search catalog with filters and pagination
**Input**:
```json
{
  "filters": {
    "brand": "Toyota",
    "model": "Camry",
    "year": 2020,
    "min_price": 20000,
    "max_price": 40000,
    "status": "available"
  },
  "page": 1,
  "per_page": 20
}
```
**Output**:
```json
{
  "listings": [...],
  "total_count": 150,
  "page": 1,
  "per_page": 20,
  "filters_applied": {...}
}
```

#### 3. catalog.listing.get
**Purpose**: Get detailed information about a specific listing
**Input**: `{"id": "listing_abc123"}`
**Output**: Full listing details

#### 4. catalog.photos.list
**Purpose**: List photos for a specific listing
**Input**: `{"listing_id": "listing_abc123"}`
**Output**:
```json
{
  "listing_id": "listing_abc123",
  "photos": [],
  "total_count": 0
}
```

#### 5. catalog.listing.use
**Purpose**: Mark a listing as used/reserved
**Input**: `{"id": "listing_abc123"}`
**Output**:
```json
{
  "id": "listing_abc123",
  "status": "used",
  "used_at": 1767091200,
  "used_by": "admin_user",
  "message": "Listing marked as used"
}
```

### UI Implementation

#### Admin UI Updates (`ui/admin/index.html`)
Added three new sections:

1. **Catalog Search Section**
   - Load Filters button
   - Dynamic filter dropdowns (brand, model, year)
   - Price range inputs (min/max)
   - Search button
   - Results display

2. **CSV Import Section**
   - Filename input
   - CSV data textarea
   - Import button
   - Results display with import statistics

#### JavaScript Functions (`ui/admin/src/app.js`)
- `loadFilters()`: Calls catalog.filters.get and populates dropdowns
- `searchListings()`: Calls catalog.listings.search with selected filters
- `runImport()`: Calls import.run with CSV data

### Files Modified
- `registry/capabilities.yaml`: Added 5 catalog capabilities
- `registry/capabilities.json`: Generated from YAML
- `registry/ui.yaml`: Added catalog capabilities to admin and public UIs
- `registry/ui.json`: Generated from YAML
- `adapters/car-storage/invoke.php`: Implemented all 5 catalog capabilities
- `ui/admin/index.html`: Added search and import UI sections
- `ui/admin/src/app.js`: Added JavaScript functions

## Step 9: Smoke Tests

### Goal
Verify that all functionality works correctly with automated tests.

### Test Suite (`tests/test-steps-7-9.php`)

#### Test Categories

1. **catalog.filters.get Tests**
   - Returns HTTP 200
   - Returns filter lists (brands, models, years, price_ranges)

2. **catalog.listings.search Tests**
   - Returns HTTP 200
   - Returns listings array and total_count

3. **import.run Tests**
   - Returns HTTP 200
   - Returns import status and statistics
   - Increases listing count after import

4. **Idempotency Tests**
   - Duplicate import returns 200
   - Duplicate import detected (status: "duplicate")
   - Duplicate import doesn't increase count

5. **Internal Capability Protection Tests**
   - Direct call to storage.listings.upsert_batch returns 403
   - Direct call to parser.parse_csv returns 403

6. **Additional Catalog Tests**
   - catalog.listing.get works
   - catalog.photos.list works
   - catalog.listing.use marks listing as used

### Running Tests

```bash
# From host (if network allows)
cd tests
PLATFORM_URL="http://localhost:8080" \
API_KEY_ADMIN="admin_secret_key_12345" \
php test-steps-7-9.php

# From inside container (recommended)
docker exec -e PLATFORM_URL="http://localhost" \
  -e API_KEY_ADMIN="admin_secret_key_12345" \
  cabinet-platform \
  php /tmp/test-steps-7-9.php
```

### Expected Results
- ✓ All capability tests should pass
- ✓ Import should increase listing count
- ✓ Duplicate import should be detected
- ✓ Internal capabilities should be protected (403)

## Configuration Changes

### Docker Compose (`docker-compose.yml`)
Added environment variables:
```yaml
- ENABLE_AUTH=${ENABLE_AUTH:-true}
- API_KEY_ADMIN=${API_KEY_ADMIN:-admin_secret_key_12345|admin|admin|admin_user}
- API_KEY_PUBLIC=${API_KEY_PUBLIC:-public_secret_key_67890|public|guest|public_user}
```

### Registry Files
Updated all registry JSON files to match YAML files:
- `registry/ui.json`: Contains catalog capabilities for admin and public UIs
- `registry/capabilities.json`: Contains all capability definitions including catalog

## Architecture Decisions

### 1. Parser in Adapter vs Separate Service
**Decision**: Implemented parser as a capability in car-storage adapter
**Rationale**: 
- Simpler architecture
- Parser logic is closely tied to storage format
- Avoids unnecessary service boundaries for simple CSV parsing

### 2. Catalog Capabilities in Storage Adapter
**Decision**: Implemented catalog capabilities in car-storage adapter
**Rationale**:
- Catalog searches the same data store as storage
- Avoids data duplication
- Simpler to maintain

### 3. Internal Capability Protection
**Decision**: Use CapabilityExecutor chain validation
**Rationale**:
- Centralized security enforcement
- Explicit allowed chains
- Fail-closed by default

## Testing Strategy

### Unit Tests
- Individual capability tests
- Idempotency tests
- Security tests

### Integration Tests
- Full import workflow
- Search with filters
- Marking listings as used

### Manual Testing
- UI functionality
- Filter loading
- Search results
- Import feedback

## Security Considerations

1. **Authentication**: All requests require X-API-Key header
2. **Authorization**: UI-based access control enforced by Policy
3. **Internal Capabilities**: Cannot be called directly from UI
4. **Chain Validation**: Only authorized parent capabilities can invoke internal capabilities
5. **Audit Logging**: All operations logged for traceability

## Usage Examples

### Load Filters
```bash
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin_secret_key_12345" \
  -d '{
    "capability": "catalog.filters.get",
    "payload": {}
  }'
```

### Search Listings
```bash
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin_secret_key_12345" \
  -d '{
    "capability": "catalog.listings.search",
    "payload": {
      "filters": {"brand": "Toyota"},
      "page": 1,
      "per_page": 20
    }
  }'
```

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

### Mark Listing as Used
```bash
curl -X POST http://localhost:8080/api/invoke \
  -H "Content-Type: application/json" \
  -H "X-API-Key: admin_secret_key_12345" \
  -d '{
    "capability": "catalog.listing.use",
    "payload": {
      "id": "listing_abc123"
    }
  }'
```

## Future Enhancements

1. **Photo Management**: Implement actual photo storage and retrieval
2. **Advanced Filters**: Add more filter options (color, mileage, features)
3. **Sorting**: Add sorting options for search results
4. **Saved Searches**: Allow users to save and reuse search filters
5. **Export**: Export search results to CSV or other formats
6. **Batch Operations**: Bulk mark listings as used/reserved

## Troubleshooting

### Issue: "UI not allowed to use capability"
**Solution**: 
1. Check that capability is listed in `registry/ui.yaml`
2. Regenerate `registry/ui.json` from YAML
3. Restart platform container

### Issue: "Internal capability called directly"
**Solution**: This is expected - internal capabilities cannot be called from UI. Use the parent capability (e.g., import.run) instead.

### Issue: Import not increasing count
**Solution**: 
1. Check CSV format matches expected columns
2. Verify external_id is present in CSV
3. Check adapter logs for errors

### Issue: Registry changes not taking effect
**Solution**:
1. Update both YAML and JSON files
2. Restart platform container: `docker compose restart platform`
3. Or call reload endpoint: `POST /control/reload-registry`

## Summary

Steps 7-9 have been successfully implemented with:
- ✅ Import orchestration through core (no direct HTTP from adapters)
- ✅ 5 catalog capabilities for search functionality
- ✅ UI integration with search interface and import form
- ✅ Comprehensive smoke tests
- ✅ Security enforcement for internal capabilities
- ✅ Idempotency for imports
- ✅ Full documentation and examples

The implementation follows the platform's architecture principles:
- Thin core (platform)
- Business logic in adapters
- Registry-driven configuration
- Fail-closed security
- Observable operations (audit logging)
