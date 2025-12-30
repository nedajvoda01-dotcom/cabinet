# Step 9: Canonical Adapter Separation

## Status: Future Enhancement (Not Required for MVP)

This document outlines the plan for Step 9, which involves splitting the monolithic `car-storage` adapter into smaller, specialized adapters. This is a future enhancement that can be done after the MVP is fully operational.

## Current State

Currently, the `car-storage` adapter handles multiple responsibilities:
- **Storage**: Direct database access and data persistence
- **Parser**: CSV parsing and data normalization
- **Catalog**: Search, filtering, and listing queries
- **Import**: Orchestration of import workflows

This works fine for MVP, but violates the single responsibility principle and makes the adapter harder to maintain as it grows.

## Proposed Separation

Split `car-storage` into four specialized adapters:

### 1. storage-adapter
**Responsibility**: Data persistence and retrieval (DB owner)

**Capabilities**:
- `storage.listings.create`
- `storage.listings.read`
- `storage.listings.update`
- `storage.listings.delete`
- `storage.listings.upsert_batch` (internal-only)
- `storage.imports.register` (internal-only)
- `storage.imports.mark_done` (internal-only)

**Internal-only**: Yes, for write operations
**Allowed parents**: catalog-adapter, import-adapter

### 2. parser-adapter
**Responsibility**: CSV parsing and data normalization

**Capabilities**:
- `parser.parse_csv` (internal-only)
- `parser.validate_csv` (internal-only)
- `parser.calculate_hash`

**Internal-only**: Yes
**Allowed parents**: import-adapter

### 3. catalog-adapter
**Responsibility**: Search, filtering, and UI-facing queries

**Capabilities**:
- `catalog.listings.search`
- `catalog.listing.get`
- `catalog.filters.get`
- `catalog.photos.list`
- `catalog.listing.use`

**Internal-only**: No (public-facing)
**Allowed parents**: N/A (can be called from UI)

**Note**: This adapter calls storage-adapter internally for data access.

### 4. import-adapter
**Responsibility**: Import workflow orchestration

**Capabilities**:
- `import.run`
- `import.status`
- `import.history`

**Internal-only**: No (admin-facing)
**Allowed parents**: N/A

**Note**: This adapter orchestrates calls to parser-adapter and storage-adapter.

## Implementation Steps

### Step 1: Create New Adapter Directories

```bash
./scripts/new-adapter.sh storage-adapter
./scripts/new-adapter.sh parser-adapter
./scripts/new-adapter.sh catalog-adapter
./scripts/new-adapter.sh import-adapter
```

### Step 2: Move Capabilities to New Adapters

Split the existing `adapters/car-storage/invoke.php` into four separate files:

1. Copy storage-related handlers to `adapters/storage-adapter/invoke.php`
2. Copy parser-related handlers to `adapters/parser-adapter/invoke.php`
3. Copy catalog-related handlers to `adapters/catalog-adapter/invoke.php`
4. Copy import-related handlers to `adapters/import-adapter/invoke.php`

### Step 3: Update Registry

Update `registry/adapters.yaml`:

```yaml
adapters:
  storage-adapter:
    url: http://adapter-storage
    timeout: 30
    description: "Data persistence and retrieval"
    
  parser-adapter:
    url: http://adapter-parser
    timeout: 30
    description: "CSV parsing and normalization"
    
  catalog-adapter:
    url: http://adapter-catalog
    timeout: 30
    description: "Search and filtering for UI"
    
  import-adapter:
    url: http://adapter-import
    timeout: 60
    description: "Import workflow orchestration"
```

Update `registry/capabilities.yaml`:

```yaml
capabilities:
  # Storage adapter capabilities
  storage.listings.create:
    adapter: storage-adapter
    internal_only: true
    allowed_parents:
      - catalog.listing.create
      - import.run
      
  storage.listings.upsert_batch:
    adapter: storage-adapter
    internal_only: true
    allowed_parents:
      - import.run
      
  # Parser adapter capabilities
  parser.parse_csv:
    adapter: parser-adapter
    internal_only: true
    allowed_parents:
      - import.run
      
  # Catalog adapter capabilities
  catalog.listings.search:
    adapter: catalog-adapter
    internal_only: false
    description: "Search listings with filters"
    
  # Import adapter capabilities
  import.run:
    adapter: import-adapter
    internal_only: false
    description: "Run CSV import"
```

### Step 4: Update docker-compose.yml

Add new services:

```yaml
services:
  adapter-storage:
    image: php:8.2-apache
    container_name: cabinet-adapter-storage
    networks:
      - mesh
    expose:
      - "80"
    volumes:
      - ./adapters/storage-adapter:/var/www/html
      - ./storage:/var/lib/cabinet/storage
    command: >
      bash -c "
        a2enmod rewrite &&
        apache2-foreground
      "

  adapter-parser:
    image: php:8.2-apache
    container_name: cabinet-adapter-parser
    networks:
      - mesh
    expose:
      - "80"
    volumes:
      - ./adapters/parser-adapter:/var/www/html
    command: >
      bash -c "
        a2enmod rewrite &&
        apache2-foreground
      "

  adapter-catalog:
    image: php:8.2-apache
    container_name: cabinet-adapter-catalog
    networks:
      - mesh
    expose:
      - "80"
    volumes:
      - ./adapters/catalog-adapter:/var/www/html
    command: >
      bash -c "
        a2enmod rewrite &&
        apache2-foreground
      "

  adapter-import:
    image: php:8.2-apache
    container_name: cabinet-adapter-import
    networks:
      - mesh
    expose:
      - "80"
    volumes:
      - ./adapters/import-adapter:/var/www/html
    command: >
      bash -c "
        a2enmod rewrite &&
        apache2-foreground
      "
```

### Step 5: Implement Cross-Adapter Communication

Update adapters to call other adapters through the core platform:

**Example: catalog-adapter calling storage-adapter**

```php
// In adapters/catalog-adapter/invoke.php

function searchListings($payload) {
    // Call storage-adapter through the core platform
    // This is done automatically by CapabilityExecutor
    // when we invoke a capability that's handled by another adapter
    
    // The catalog-adapter just needs to format the query
    // and return results
    return [
        'listings' => [...],
        'total_count' => 100,
        'page' => 1
    ];
}
```

**Important**: Adapters should NOT make direct HTTP calls to other adapters. All communication goes through the core platform's CapabilityExecutor, which handles authentication, authorization, rate limiting, and audit logging.

### Step 6: Update Chain Rules

Update `registry/capabilities.yaml` to reflect new chains:

```yaml
capabilities:
  catalog.listings.search:
    adapter: catalog-adapter
    internal_only: false
    # catalog-adapter can call storage-adapter capabilities internally
    
  storage.listings.read:
    adapter: storage-adapter
    internal_only: true
    allowed_parents:
      - catalog.listings.search
      - catalog.listing.get
      
  import.run:
    adapter: import-adapter
    internal_only: false
    
  parser.parse_csv:
    adapter: parser-adapter
    internal_only: true
    allowed_parents:
      - import.run
      
  storage.listings.upsert_batch:
    adapter: storage-adapter
    internal_only: true
    allowed_parents:
      - import.run
```

### Step 7: Validate

```bash
# Validate registry
./scripts/validate-registry.sh

# Check architecture
./scripts/check-architecture.sh

# Run all tests
./scripts/ci-verify.sh

# Test network isolation with new adapters
./tests/test-network-isolation.sh
```

### Step 8: Deprecate car-storage

Once all capabilities are migrated:

1. Update UI to use new capability names (if any changed)
2. Remove `car-storage` from docker-compose.yml
3. Remove `adapters/car-storage/` directory
4. Update documentation

## Benefits of Separation

1. **Single Responsibility**: Each adapter has one clear purpose
2. **Independent Scaling**: Can scale catalog-adapter separately from storage-adapter
3. **Easier Testing**: Smaller, focused adapters are easier to test
4. **Better Security**: Finer-grained control over internal capabilities
5. **Team Ownership**: Different teams can own different adapters
6. **Simpler Debugging**: Smaller codebases are easier to debug

## Key Principle: Communication Through Core

Even with separated adapters, they MUST NOT communicate directly:

❌ **Wrong** (direct HTTP):
```php
// In catalog-adapter
$storageUrl = 'http://adapter-storage/invoke';
$response = file_get_contents($storageUrl, ...);
```

✅ **Correct** (through core):
```php
// The core platform handles this automatically
// Catalog-adapter just returns data
// Storage calls are orchestrated by CapabilityExecutor
```

The CapabilityExecutor ensures that every capability invocation (including internal chains) goes through the complete security pipeline:

```
catalog.listings.search (UI call)
  ↓
CapabilityExecutor
  ↓
catalog-adapter (handles search logic)
  ↓
CapabilityExecutor (internal chain)
  ↓
storage-adapter (fetches data)
```

All communication flows through the core, ensuring:
- Authentication
- Authorization
- Rate limiting
- Audit logging
- Network isolation enforcement

## Timeline

This separation is a **future enhancement** and is not required for MVP. It should be considered after:

1. MVP is fully operational in production
2. There's clear evidence that the monolithic adapter is becoming a maintenance burden
3. There's a need to scale different parts independently
4. Multiple teams need to work on different capabilities

## Conclusion

Step 9 provides a clear path for evolving the architecture as the platform grows, while maintaining all the security and isolation guarantees established in Steps 6-8.

The beauty of the Cabinet architecture is that this refactoring requires **zero changes to the platform code** — only registry updates and new adapter services!
