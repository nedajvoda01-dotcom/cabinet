# Platform Monorepo Architecture Guide

This document provides a comprehensive overview of the Cabinet Platform Monorepo architecture.

## Table of Contents

1. [Architecture Overview](#architecture-overview)
2. [Directory Structure](#directory-structure)
3. [Trust Boundaries](#trust-boundaries)
4. [Component Responsibilities](#component-responsibilities)
5. [Development Workflow](#development-workflow)
6. [Testing Strategy](#testing-strategy)
7. [Release Process](#release-process)

## Architecture Overview

The Cabinet Platform Monorepo follows a **trust-boundary-based architecture** with strict separation of concerns:

```
┌─────────────────────────────────────────────────┐
│                   UI Layer                       │
│         (Desktop Control Panel)                  │
│              API Calls Only                      │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│              Platform Core                       │
│    (Trust Boundary, Security, Orchestration)    │
│                                                  │
│  ┌────────────────────────────────────────┐    │
│  │     Security Conveyor Pipeline         │    │
│  │  Auth → Signature → Nonce → Hierarchy  │    │
│  └────────────────────────────────────────┘    │
│                                                  │
│  ┌────────────────────────────────────────┐    │
│  │         Result Gate (Always On)         │    │
│  └────────────────────────────────────────┘    │
└────────────────┬────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│              Adapter Layer                       │
│      (External Integrations - Untrusted)        │
│                                                  │
│  Parser | Photos | Robot | Storage | Browser    │
│                                                  │
│  Uniform Interface: /invoke /descriptor /health │
└─────────────────────────────────────────────────┘
                 │
                 ▼
┌─────────────────────────────────────────────────┐
│            Shared Contracts                      │
│         (Single Source of Truth)                 │
│                                                  │
│  Primitives | Vectors | Versioned (N/N-1)       │
└─────────────────────────────────────────────────┘
```

## Directory Structure

```
cabinet/
├── platform/              # Core trust boundary
│   ├── src/
│   │   ├── Application/   # Use cases, commands, orchestration
│   │   ├── Domain/        # Business rules (no I/O)
│   │   ├── Infrastructure/# DB, queue, crypto, integrations
│   │   ├── Http/          # Routing, security boundary
│   │   └── Bootstrap/     # Container & wiring
│   ├── public/            # HTTP entry point
│   ├── bin/               # CLI tools (worker)
│   └── tests/             # Platform tests
│
├── ui/                    # User interfaces
│   └── desktop/           # Desktop control panel
│       ├── src/
│       └── package.json
│
├── adapters/              # External integrations (untrusted)
│   ├── parser/            # Content parsing
│   ├── photos/            # Photo processing
│   ├── robot/             # Robot automation
│   ├── storage/           # Storage service
│   └── browser-context/   # Browser automation
│
├── shared/                # Single source of truth
│   ├── contracts/         # Type definitions, primitives
│   │   ├── primitives/    # Core type definitions
│   │   ├── vectors/       # Test vectors
│   │   └── implementations/ # Language-specific
│   ├── canonicalization/  # Canonicalization rules
│   └── crypto/            # Crypto primitives
│
├── ci/                    # CI/CD pipelines
│   ├── pipelines/         # Pipeline definitions
│   ├── jobs/              # Job templates
│   ├── policies/          # Quality gates
│   └── secrets/           # Secret templates
│
├── delivery/              # Release artifacts
│   ├── manifests/         # Artifact definitions
│   ├── compat/            # Compatibility checker
│   ├── signing/           # Signing policies
│   └── rollout/           # Deployment playbooks
│
├── runtime/               # Runtime configurations
│   ├── worker/            # Worker runtime
│   ├── http/              # HTTP server
│   └── queue/             # Queue configuration
│
├── tests/                 # Cross-cutting tests
│   ├── architecture/      # Boundary enforcement
│   ├── contracts/         # Contract validation
│   └── e2e-smoke/         # E2E critical path
│
├── docs/                  # Documentation
├── config/                # Configuration files
├── scripts/               # Utility scripts
│   ├── ci/                # CI scripts
│   └── rollout/           # Deployment scripts
│
├── composer.json          # PHP dependencies
├── package.json           # Node.js workspace
├── docker-compose.yml     # Local development
└── Makefile               # Common tasks
```

## Trust Boundaries

### 1. Platform (Trust Core)

**Location:** `platform/`

**Purpose:** 
- Security enforcement
- Protocol handling
- Orchestration
- Policy enforcement

**Rules:**
- ❌ Cannot import adapters
- ❌ Cannot import UI
- ✅ Can import shared contracts
- ✅ Single HTTP entrypoint
- ✅ All security decisions

**Key Components:**
- Security conveyor pipeline
- Result gate (always on)
- Authentication & authorization
- Command bus
- Worker daemon

### 2. Adapters (Untrusted Extensions)

**Location:** `adapters/`

**Purpose:**
- External system integrations
- Business logic execution
- Data processing

**Rules:**
- ❌ Cannot import platform
- ✅ Can import shared contracts
- ✅ Must implement uniform interface:
  - `/invoke` - Execute operation
  - `/descriptor` - Metadata
  - `/health` - Health check
- ✅ Must have fallback implementation

**Available Adapters:**
- Parser - Content parsing and extraction
- Photos - Photo processing and optimization
- Robot - External automation and robot control
- Storage - Storage service integration
- Browser Context - Browser automation

### 3. UI (Operator Console)

**Location:** `ui/desktop/`

**Purpose:**
- Desktop-only control panel
- Operator interface
- State reflection

**Rules:**
- ❌ Cannot import platform code
- ✅ API-only communication
- ✅ Can import shared contracts
- ❌ No business logic
- ✅ Read-heavy

### 4. Shared (Single Source of Truth)

**Location:** `shared/`

**Purpose:**
- Contract definitions
- Type definitions
- Canonicalization
- Crypto primitives

**Rules:**
- ❌ Cannot depend on platform
- ❌ Cannot depend on adapters
- ❌ Cannot depend on UI
- ✅ Versioned (N/N-1 compatibility)
- ✅ Language-agnostic

## Component Responsibilities

### Platform Responsibilities

**Does:**
- Authentication & authorization
- Signature verification
- Nonce validation
- Hierarchy enforcement
- Rate limiting
- Pipeline orchestration
- Job queue management
- Audit logging

**Does NOT:**
- Parse content
- Process photos
- Call external APIs
- Make business decisions

### Adapter Responsibilities

**Does:**
- External API calls
- Business logic
- Data transformation
- Error handling

**Does NOT:**
- Security enforcement
- Authentication
- Authorization
- Access control

### UI Responsibilities

**Does:**
- Display data
- User input
- API calls
- Local state management

**Does NOT:**
- Security decisions
- Business logic
- Direct database access
- Platform code imports

## Development Workflow

### 1. Setup

```bash
# Clone repository
git clone <repo>
cd cabinet

# Install dependencies
make install

# Start application
make start
```

### 2. Make Changes

Follow trust boundary rules:
- Platform: Security, protocol, orchestration only
- Adapters: Business logic and integrations
- UI: Display and user interaction
- Shared: Contracts and types only

### 3. Run Tests

```bash
# All tests
make test

# Individual suites
make test-arch        # Architectural boundaries
make test-contracts   # Contract validation
make test-compat      # N/N-1 compatibility
make test-e2e         # E2E smoke tests
```

### 4. Commit

All merge blockers must pass:
- ✅ Architectural boundaries
- ✅ Contract parity
- ✅ N/N-1 compatibility

### 5. Pull Request

CI pipeline will run:
1. Platform tests
2. UI tests
3. Contract validation ⚠️ MERGE BLOCKER
4. Architectural tests ⚠️ MERGE BLOCKER
5. Compatibility check ⚠️ MERGE BLOCKER
6. Security scan

## Testing Strategy

### Architectural Tests

**Purpose:** Enforce trust boundaries

**Location:** `tests/architecture/boundary-tests.php`

**Checks:**
- Platform doesn't import adapters or UI
- Adapters don't import platform
- UI doesn't import platform
- Shared has no external dependencies

**Run:**
```bash
make test-arch
```

### Contract Tests

**Purpose:** Ensure contract consistency

**Location:** `tests/contracts/`

**Includes:**
- Parity tests - All contracts defined
- Smoke tests - Contracts used correctly
- Compatibility - N/N-1 support

**Run:**
```bash
make test-contracts
```

### E2E Tests

**Purpose:** Validate critical path

**Location:** `tests/e2e-smoke/critical-path.php`

**Tests:**
- Health endpoint
- Platform startup
- Worker daemon
- File structure

**Run:**
```bash
make test-e2e
```

### Compatibility Check

**Purpose:** Prevent breaking changes

**Location:** `delivery/compat/compatibility-checker.php`

**Checks:**
- Version consistency
- Primitive stability
- Vector compatibility
- N/N-1 support

**Run:**
```bash
make test-compat
```

## Release Process

### 1. Version Bump

Update:
- `delivery/manifests/artifacts.json`
- `shared/contracts/versions.json`

### 2. Run Tests

```bash
make test
```

All tests must pass.

### 3. Tag Release

```bash
git tag -a v1.0.0 -m "Release 1.0.0"
git push origin v1.0.0
```

### 4. Release Pipeline

Automatically:
1. Builds artifacts
2. Verifies compatibility
3. Signs artifacts
4. Publishes to registry
5. Executes rollout playbooks

### 5. Verify Deployment

Check health endpoints and metrics.

## Key Design Principles

1. **Frozen Core** - Platform is conservative and stable
2. **Fail-Closed Security** - Security failures prevent execution
3. **Determinism** - Predictable pipeline transitions
4. **Trust Boundaries** - Strict separation enforced by tests
5. **Contract Governance** - N/N-1 compatibility required

## Quick Reference

### Commands

```bash
make install       # Install dependencies
make test          # Run all tests
make start         # Start application
make clean         # Clean build artifacts
make help          # Show available commands
```

### Test Files

```bash
php tests/run-all.php                           # All tests
php tests/architecture/boundary-tests.php       # Boundaries
php tests/contracts/parity-tests.php            # Contract parity
php tests/contracts/smoke-tests.php             # Contract smoke
php delivery/compat/compatibility-checker.php   # Compatibility
php tests/e2e-smoke/critical-path.php           # E2E smoke
```

### Documentation

- `MONOREPO-README.md` - Main README
- `MIGRATION-GUIDE.md` - Migration instructions
- `docs/CI-CD-SETUP.md` - CI/CD documentation
- `HIERARCHY-GUIDE.md` - Authorization model
- `SECURITY-IMPLEMENTATION.md` - Security details

## Support

For questions:
1. Check documentation in `docs/`
2. Run `make test` to verify setup
3. Review architectural tests for rules
4. Check adapter examples
