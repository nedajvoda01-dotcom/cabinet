# Monorepo Migration Guide

This document describes the migration from the original Cabinet structure to the Platform Monorepo architecture.

## Migration Overview

The repository has been restructured to enforce strict trust boundaries and separation of concerns following a platform monorepo architecture.

## What Changed

### Directory Structure

**Before:**
```
cabinet/
├── app/
│   ├── backend/
│   └── frontend/
├── shared/
├── tests/
├── docs/
└── scripts/
```

**After:**
```
cabinet/
├── platform/          # app/backend → platform/
├── ui/               
│   └── desktop/       # app/frontend → ui/desktop/
├── adapters/          # Integrations extracted
│   ├── parser/
│   ├── photos/
│   ├── robot/
│   ├── storage/
│   └── browser-context/
├── shared/            # Single source of truth
│   └── contracts/
├── ci/                # CI pipelines
├── delivery/          # Release artifacts
├── runtime/           # Runtime configs
├── tests/             # Architecture & contracts tests
├── docs/
├── config/
└── scripts/
```

### Component Mapping

1. **Backend → Platform**
   - `app/backend/` → `platform/`
   - Namespace: `Cabinet\Backend\` → `Cabinet\Platform\`
   - Purpose: Core trust boundary, security, orchestration

2. **Frontend → UI**
   - `app/frontend/` → `ui/desktop/`
   - API-only communication with platform
   - No direct code imports from platform

3. **Integrations → Adapters**
   - External integrations extracted to `adapters/`
   - Uniform interface: `/invoke`, `/descriptor`, `/health`
   - No platform code dependencies

4. **Shared Contracts**
   - Remains in `shared/contracts/`
   - Single source of truth
   - Versioned with N/N-1 compatibility

## Namespace Changes

Update import statements:

**Before:**
```php
use Cabinet\Backend\Application\Commands\CreateTaskCommand;
use Cabinet\Backend\Domain\Task;
```

**After:**
```php
use Cabinet\Platform\Application\Commands\CreateTaskCommand;
use Cabinet\Platform\Domain\Task;
```

## Autoloader Changes

The `composer.json` has been updated:

**Before:**
```json
{
  "autoload": {
    "psr-4": {
      "Cabinet\\Backend\\": "app/backend/src/"
    }
  }
}
```

**After:**
```json
{
  "autoload": {
    "psr-4": {
      "Cabinet\\Platform\\": "platform/src/",
      "Cabinet\\Adapters\\Parser\\": "adapters/parser/",
      "Cabinet\\Adapters\\Photos\\": "adapters/photos/",
      "Cabinet\\Adapters\\Robot\\": "adapters/robot/",
      "Cabinet\\Adapters\\Storage\\": "adapters/storage/",
      "Cabinet\\Adapters\\BrowserContext\\": "adapters/browser-context/"
    }
  }
}
```

## Running the Application

### Before
```bash
# Backend
php -S localhost:8080 -t app/backend/public app/backend/public/index.php

# Worker
php app/backend/bin/worker.php

# Frontend
cd app/frontend && npm run dev
```

### After (One Command)
```bash
./scripts/start.sh
```

Or manually:
```bash
# Backend
php -S localhost:8080 -t platform/public platform/public/index.php

# Worker
php platform/bin/worker.php

# Frontend
cd ui/desktop && npm run dev
```

## Testing

### New Test Structure

1. **Architectural Tests** - `tests/architecture/`
   - Enforces trust boundaries
   - Prevents illegal imports

2. **Contract Tests** - `tests/contracts/`
   - Parity tests
   - Smoke tests
   - N/N-1 compatibility

3. **E2E Tests** - `tests/e2e-smoke/`
   - Critical path smoke tests

### Running Tests

```bash
# All acceptance tests
php tests/run-all.php

# Individual test suites
php tests/architecture/boundary-tests.php
php tests/contracts/parity-tests.php
php tests/contracts/smoke-tests.php
php tests/e2e-smoke/critical-path.php

# Compatibility check (merge blocker)
php delivery/compat/compatibility-checker.php
```

## CI/CD Changes

CI configuration moved to `ci/pipelines/main.yml`. The pipeline now includes:

1. Platform tests
2. UI build and tests
3. Contract validation (merge blocker)
4. Architecture tests (merge blocker)
5. Security scans

## Adapter Development

To develop new adapters:

1. Create directory: `adapters/your-adapter/`
2. Implement required endpoints:
   - `/invoke` - Main functionality
   - `/descriptor` - Metadata
   - `/health` - Health check
3. Add fallback implementation
4. Update `delivery/manifests/artifacts.json`
5. No platform code imports allowed

## Trust Boundaries (Enforced by Tests)

1. **Platform** cannot import adapters or UI
2. **Adapters** cannot import platform
3. **UI** cannot import platform
4. All can import **shared** contracts

These rules are enforced by `tests/architecture/boundary-tests.php` and will fail CI if violated.

## Migration Checklist

If you have local changes:

- [ ] Run `composer install` to update autoloader
- [ ] Update namespace imports (Backend → Platform)
- [ ] Update paths to platform/public/index.php
- [ ] Update paths to platform/bin/worker.php
- [ ] Update UI paths (app/frontend → ui/desktop)
- [ ] Run tests: `php tests/run-all.php`
- [ ] Verify build works: `./scripts/start.sh`

## Breaking Changes

None - this is a structural reorganization. All functionality remains the same.

## Support

For questions or issues:
- See `MONOREPO-README.md` for architecture overview
- Run `php tests/run-all.php` to verify setup
- Check `ci/README.md` for CI/CD details
- Review `delivery/README.md` for release process
