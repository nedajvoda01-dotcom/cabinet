# Cabinet Platform Monorepo

Cabinet is a **secure orchestration and control plane** following strict trust-boundary architecture.

## 🏗️ Architecture Overview

This monorepo implements a platform with enforced separation of concerns:

```
cabinet/
├── platform/       # Core trust boundary (HTTP, security, orchestration)
├── ui/            # User interfaces (desktop-only control panel)
├── adapters/      # External integrations (untrusted extensions)
├── shared/        # Shared contracts and types (single source of truth)
├── ci/            # CI/CD pipelines and policies
├── delivery/      # Release artifacts and rollout playbooks
├── runtime/       # Runtime configurations
├── tests/         # Cross-cutting tests (architecture, contracts, e2e)
├── docs/          # Documentation
├── config/        # Configuration files
└── scripts/       # Utility scripts
```

## 🚀 Quick Start

### One-Command Startup

```bash
./scripts/start.sh
```

This will:
1. Install dependencies
2. Start platform backend (port 8080)
3. Start worker daemon
4. Start desktop UI (port 3000)

### Manual Startup

#### Backend Platform
```bash
php -S localhost:8080 -t platform/public platform/public/index.php
```

#### Worker
```bash
php platform/bin/worker.php
```

#### Desktop UI
```bash
cd ui/desktop
npm install
npm run dev
```

### Docker Compose

```bash
docker-compose up
```

## 🛡️ Trust Boundaries

### Platform (Trust Core)
- **Location**: `platform/`
- **Purpose**: Security enforcement, protocol handling, orchestration
- **Rules**: 
  - Cannot import adapters or UI
  - Only imports shared contracts
  - Single HTTP entrypoint
  - All security decisions here

### Adapters (Untrusted Extensions)
- **Location**: `adapters/`
- **Purpose**: External system integrations
- **Rules**:
  - Cannot import platform code
  - Only imports shared contracts
  - Uniform interface: `/invoke`, `/descriptor`, `/health`
  - Must have fallback implementations

### UI (Operator Console)
- **Location**: `ui/`
- **Purpose**: Desktop-only control panel
- **Rules**:
  - Cannot import platform code
  - API-only communication with platform
  - No business logic
  - Read-heavy, reflects backend state

### Shared (Single Source of Truth)
- **Location**: `shared/`
- **Purpose**: Contracts, types, canonicalization
- **Rules**:
  - Cannot depend on platform, adapters, or UI
  - Versioned with N/N-1 compatibility
  - Language-agnostic contracts

## 📋 Testing

### Run All Tests
```bash
# Architectural boundary tests
php tests/architecture/boundary-tests.php

# Contract tests
php tests/contracts/parity-tests.php
php tests/contracts/smoke-tests.php

# E2E smoke test
php tests/e2e-smoke/critical-path.php
```

### CI Pipeline
Tests run automatically on pull requests:
- Platform tests
- UI build and tests
- Contract validation
- Architecture tests
- Security scans

## 🔄 Release Process

1. **Version Bump** - Update `delivery/manifests/artifacts.json`
2. **Build** - Generate artifacts for platform, UI, adapters
3. **Compat Check** - Verify N/N-1 contract compatibility (merge blocker)
4. **Sign** - Generate signatures and provenance
5. **Publish** - Upload to registry with metadata
6. **Rollout** - Deploy using playbooks in `delivery/rollout/`

## 📦 Dependencies

### Platform (PHP)
- PHP 8.1+
- SQLite (dev mode)

### UI (Node.js)
- Node.js 18+
- Vite
- TypeScript

### Adapters
- Varies by adapter (see adapter README)

## 🔐 Security

Cabinet implements fail-closed security:
- All requests pass through security pipeline
- Signature-based authentication
- Nonce-based replay protection
- Hierarchical authorization
- Audit trail for all operations

See `SECURITY-IMPLEMENTATION.md` for details.

## 📚 Documentation

- `README.md` - This file
- `HIERARCHY-GUIDE.md` - Authorization model (normative)
- `SECURITY-IMPLEMENTATION.md` - Security architecture
- `ENCRYPTION-SCHEME.md` - Encryption details
- `ci/README.md` - CI/CD documentation
- `delivery/README.md` - Release documentation
- `adapters/README.md` - Adapter development guide

## 🎯 Design Principles

> Infrastructure must be boring.  
> Predictability beats cleverness.  
> Safety beats speed.

1. **Frozen Core** - Platform orchestration is conservative and stable
2. **Fail-Closed Security** - Security failures prevent execution
3. **Determinism** - Predictable pipeline transitions
4. **Ports & Adapters** - Growth through integrations only
5. **Trust Boundaries** - Strict separation enforced by tests

## 🧪 Development Guidelines

### Adding Features
1. Features go in adapters, not platform
2. Platform changes require architectural review
3. All changes must pass boundary tests
4. Contract changes need N/N-1 compatibility

### Code Changes
- Platform: Security, protocol, orchestration only
- Adapters: Business logic and integrations
- Shared: Contracts and types only
- UI: Display and user interaction only

## 📊 Monitoring

Cabinet provides:
- Structured JSON logging
- Persisted audit trail
- Metrics as log events
- Health endpoints for all services

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make changes following architecture rules
4. Run tests: architecture, contracts, e2e
5. Submit pull request

All PRs must pass:
- Architectural boundary tests
- Contract parity tests
- Security scans
- CI pipeline

## 📄 License

See LICENSE file for details.

## 🆘 Support

For issues and questions:
- Check documentation in `docs/`
- Review architecture tests for boundary rules
- See examples in existing adapters
