# CI/CD Setup for Platform Monorepo

This document describes the CI/CD setup for the Cabinet Platform Monorepo.

## Overview

The CI/CD system is designed around two key principles:

1. **Merge Blockers** - Certain tests must pass before code can be merged
2. **Contract Governance** - N/N-1 compatibility is strictly enforced

## Pipeline Structure

### Location
All CI configuration is in `ci/pipelines/`

### Main Pipeline (`ci/pipelines/main.yml`)

Runs on every pull request and push to main/develop.

**Stages:**

1. **Platform Tests**
   - PHP unit tests
   - Integration tests
   - Build verification

2. **UI Tests**
   - TypeScript compilation
   - Build verification
   - Linting

3. **Contract Validation** ⚠️ MERGE BLOCKER
   - Generate contracts
   - Run parity tests
   - Run contract smoke tests

4. **Architectural Tests** ⚠️ MERGE BLOCKER
   - Boundary enforcement
   - Dependency rules
   - Trust boundary validation

5. **Compatibility Check** ⚠️ MERGE BLOCKER
   - N/N-1 contract compatibility
   - Breaking change detection
   - Version validation

6. **Security Scan**
   - Dependency scanning
   - Vulnerability checks
   - Static analysis

### Release Pipeline

Runs on tagged releases (e.g., `v1.0.0`)

**Stages:**

1. **Build Artifacts**
   - Platform bundle
   - UI build
   - Adapter packages

2. **Compatibility Verification**
   - Verify N/N-1 compatibility
   - Generate compatibility report

3. **Sign Artifacts**
   - Generate signatures
   - Create provenance metadata

4. **Publish**
   - Upload to registry
   - Update artifact manifests
   - Create release notes

5. **Rollout**
   - Execute rollout playbooks
   - Health gate checks
   - Gradual deployment

## Merge Blockers

These tests **must pass** before code can be merged:

### 1. Architectural Boundary Tests
```bash
php tests/architecture/boundary-tests.php
```

**Enforces:**
- Platform cannot import adapters or UI
- Adapters cannot import platform
- UI cannot import platform
- Shared has no dependencies

**Why it blocks:** Violating trust boundaries can lead to security issues and architectural decay.

### 2. Contract Parity Tests
```bash
php tests/contracts/parity-tests.php
```

**Enforces:**
- All primitives defined
- Vectors are valid
- No missing definitions

**Why it blocks:** Broken contracts break all services that depend on them.

### 3. Compatibility Check
```bash
php delivery/compat/compatibility-checker.php
```

**Enforces:**
- N/N-1 compatibility
- No breaking changes without migration
- Version consistency

**Why it blocks:** Breaking compatibility can cause runtime failures in production.

## Local Development Workflow

### Before Committing

Run acceptance tests locally:
```bash
php tests/run-all.php
```

This runs all merge blockers and ensures your code will pass CI.

### Running Individual Tests

```bash
# Architectural tests only
php tests/architecture/boundary-tests.php

# Contract tests only
php tests/contracts/parity-tests.php
php tests/contracts/smoke-tests.php

# Compatibility check
php delivery/compat/compatibility-checker.php

# E2E smoke test
php tests/e2e-smoke/critical-path.php
```

## Adding New Tests

### Architecture Tests

Edit `tests/architecture/boundary-tests.php` to add new boundary rules.

### Contract Tests

Edit `tests/contracts/parity-tests.php` or `tests/contracts/smoke-tests.php`.

### E2E Tests

Add new scenarios to `tests/e2e-smoke/`.

## Handling Test Failures

### Architectural Violation

**Example:**
```
❌ Architectural violations found:
  - Platform imports adapter: SomeFile.php
```

**Fix:** Remove the import. Platform should only import shared contracts.

### Contract Parity Failure

**Example:**
```
❌ Contract parity failures:
  - Missing primitive definition: NewPrimitive.md
```

**Fix:** Add the missing primitive definition in `shared/contracts/primitives/`.

### Compatibility Failure

**Example:**
```
❌ Compatibility issues found:
  - Core primitive removed (breaking change): Scope.md
```

**Fix:** Do not remove core primitives. Add deprecation notice and migration guide instead.

## Release Process

### 1. Version Bump

Update version in:
- `delivery/manifests/artifacts.json`
- `shared/contracts/versions.json`

### 2. Update Changelog

Add entry to `shared/contracts/versions.json`:

```json
{
  "current": "1.1.0",
  "supported": ["1.1.0", "1.0.0"],
  "changelog": {
    "1.1.0": {
      "date": "2025-01-15",
      "changes": [
        "Added new feature X",
        "Fixed bug Y"
      ]
    }
  }
}
```

### 3. Run Full Test Suite

```bash
php tests/run-all.php
php delivery/compat/compatibility-checker.php
```

### 4. Tag Release

```bash
git tag -a v1.1.0 -m "Release version 1.1.0"
git push origin v1.1.0
```

### 5. Monitor Release Pipeline

Watch the release pipeline execute and verify all stages pass.

## Breaking Changes

If you must make a breaking change:

1. **Document Migration Path**
   - Create migration guide in `docs/migrations/`
   - Explain how to upgrade

2. **Maintain N-1 Support**
   - Keep old API available
   - Deprecate but don't remove

3. **Update Compatibility Matrix**
   - Mark as breaking in `delivery/manifests/artifacts.json`
   - Update supported versions

4. **Get Approval**
   - Breaking changes require explicit approval
   - Include impact analysis

## Secrets Management

Secret templates are in `ci/secrets/`. No actual secrets are committed.

For local development:
1. Copy `.env.example` to `.env`
2. Fill in required values
3. Never commit `.env`

## Monitoring

### CI Metrics

Track:
- Test execution time
- Failure rates
- Merge blocker violations

### Release Metrics

Track:
- Time to release
- Rollback rate
- Compatibility issues

## Troubleshooting

### "Composer autoloader not found"

```bash
composer install
```

### "Node modules not found"

```bash
cd ui/desktop && npm install
```

### "Platform not accessible"

Make sure platform is running:
```bash
php -S localhost:8080 -t platform/public platform/public/index.php
```

### "Tests pass locally but fail in CI"

- Check PHP version (CI uses 8.1)
- Check Node version (CI uses 18)
- Verify all files are committed
- Check for environment-specific code

## Best Practices

1. **Run tests before pushing**
   ```bash
   php tests/run-all.php
   ```

2. **Keep merge blockers fast**
   - Architectural tests: < 5 seconds
   - Contract tests: < 10 seconds
   - Compatibility: < 5 seconds

3. **Don't skip tests**
   - All merge blockers must pass
   - No exceptions

4. **Update tests with code**
   - Add tests for new boundaries
   - Update contract tests for new contracts

## Support

For CI/CD issues:
- Check pipeline logs in `ci/pipelines/`
- Review test output locally
- Verify environment matches CI
