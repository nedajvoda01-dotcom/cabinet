# ТЕМА 5 — Shared ABI: контракты и совместимость

Complete implementation of axIOm_mini ABI architecture for the Cabinet platform.

## Overview

This directory structure implements a comprehensive Application Binary Interface (ABI) that defines how the kernel, extensions, and UI components communicate. The ABI ensures version compatibility, prevents silent breakages, and enforces security policies.

## Directory Structure

```
shared/
├── contracts/          # Wire-level ABI contracts
│   ├── v1/            # Active v1 schemas
│   │   ├── envelope.schema.yaml
│   │   ├── command.schema.yaml
│   │   ├── result.schema.yaml
│   │   ├── error.schema.yaml
│   │   ├── capability.schema.yaml
│   │   ├── module.manifest.schema.yaml
│   │   ├── ui.manifest.schema.yaml
│   │   └── routing.schema.yaml
│   ├── v2/            # Future version placeholder
│   ├── versions.yaml  # Version lifecycle tracking
│   └── lifecycle.yaml # Deprecation and change rules
├── schemas/           # System intent and policy schemas
│   ├── intent/        # Intent declaration schemas
│   └── policy/        # Policy implementation schemas
├── compatibility/     # Compatibility matrices
│   ├── rules.md      # Human-readable breaking change policy
│   ├── matrix.yaml   # Kernel ↔ Contracts compatibility
│   └── system_matrix.yaml  # System ↔ Kernel compatibility
├── fixtures/          # Example files for each schema
│   └── v1/
│       ├── ipc/      # IPC message examples
│       ├── manifests/ # Manifest examples
│       └── routing/   # Routing config examples
├── test_vectors/      # Determinism test vectors
│   ├── canonical_json/
│   ├── canonical_yaml/
│   ├── hashing/
│   ├── signing/
│   └── id_generation/
└── conformance/       # Conformance test suites
    ├── profile_v1.yaml
    └── suites/
        ├── ipc_envelope.yaml
        ├── invoke_roundtrip.yaml
        ├── canonicalization.yaml
        └── routing_authz.yaml

system/
├── intent/            # High-level intent declarations
│   ├── company.intent.yaml
│   ├── ui.intent.yaml
│   ├── modules.intent.yaml
│   ├── routing.intent.yaml
│   ├── access.intent.yaml
│   ├── limits.intent.yaml
│   └── result_profiles.intent.yaml
└── policy/            # Compiled policy implementations

kernel/
└── src/
    ├── ipc/
    │   ├── validate.rs    # IPC message validation
    │   └── encode.rs      # Canonical encoding
    ├── config/
    │   ├── load_manifests.rs
    │   └── load_routes.rs
    └── routing/
        ├── graph.rs
        ├── authorize_route.rs
        └── resolve_endpoint.rs

extensions/
├── ui/
│   └── main_ui/
│       └── manifest.yaml
├── modules/
│   └── storage/
│       └── manifest.yaml
└── routing.yaml       # Runtime routing configuration

governance/
└── guards/
    └── abi_guard      # ABI change enforcement

tooling/
└── system_validator/
    └── src/
        └── main.rs    # System validation tool
```

## Key Components

### 5.1 IPC Contracts

**Location**: `shared/contracts/v1/`

Wire-level ABI for kernel ↔ extensions communication:
- **envelope.schema.yaml** - Outer wrapper for all IPC messages
- **command.schema.yaml** - Command message structure
- **result.schema.yaml** - Success result structure
- **error.schema.yaml** - Error message structure
- **capability.schema.yaml** - Capability definition structure

**Enforcement**:
- Kernel validates ALL incoming messages (`kernel/src/ipc/validate.rs`)
- Kernel encodes ALL outgoing messages canonically (`kernel/src/ipc/encode.rs`)
- Any deviation = runtime reject

### 5.2 Manifest Schemas

**Location**: `shared/contracts/v1/`

Manifests define modules and UIs:
- **module.manifest.schema.yaml** - Module definition and capabilities
- **ui.manifest.schema.yaml** - UI application definition
- **routing.schema.yaml** - Routing rules and allowlists

**Real Manifests**:
- `extensions/ui/main_ui/manifest.yaml` - Main UI manifest
- `extensions/modules/*/manifest.yaml` - Module manifests
- `extensions/routing.yaml` - Runtime routing configuration

### 5.3 System Schemas (Intent & Policy)

**Location**: `shared/schemas/`

**Intent Schemas** (`shared/schemas/intent/`):
High-level declarations of what the system SHOULD do:
- company.intent.schema.yaml
- ui.intent.schema.yaml
- modules.intent.schema.yaml
- routing.intent.schema.yaml
- access.intent.schema.yaml
- limits.intent.schema.yaml
- result_profiles.intent.schema.yaml

**Policy Schemas** (`shared/schemas/policy/`):
Concrete implementations derived from intent:
- access.schema.yaml
- routing.schema.yaml
- limits.schema.yaml
- result_profile.schema.yaml

**Validation**:
- `tooling/system_validator` validates all system files
- Output: `dist/reports/system_validation_report.json`

### 5.4 Versioning

**Location**: `shared/contracts/`

- **versions.yaml** - Tracks active, deprecated, and removed versions
- **lifecycle.yaml** - Defines deprecation and change approval process
- **v2/README.md** - Placeholder for next major version

**Rules**:
- v1.x.x = active and stable
- N/N-1 support policy (support current and previous major)
- Breaking changes REQUIRE major version bump
- Non-breaking changes ALLOW minor version bump

### 5.5 Compatibility

**Location**: `shared/compatibility/`

- **rules.md** - Human-readable breaking change policy
- **matrix.yaml** - Kernel ↔ Contracts compatibility matrix
- **system_matrix.yaml** - System schemas ↔ Kernel/Contracts compatibility

**Enforcement**:
- `governance/guards/abi_guard` checks ABI changes
- DENY if changes break compatibility without version bump
- DENY if matrix not updated

### 5.6 Artifacts

**Fixtures** (`shared/fixtures/v1/`):
Concrete examples that demonstrate schemas:
- IPC message examples (envelope, commands, results, errors)
- Manifest examples (module, UI, routing)

**Test Vectors** (`shared/test_vectors/`):
Ensure deterministic behavior:
- Canonical JSON/YAML serialization
- Content hashing (SHA-256)
- ID generation (UUID v4, deterministic)
- Digital signatures (optional)

**Conformance Suites** (`shared/conformance/`):
Formal compliance tests:
- profile_v1.yaml - Conformance requirements
- suites/ipc_envelope.yaml - Envelope validation tests
- suites/invoke_roundtrip.yaml - End-to-end invocation tests
- suites/canonicalization.yaml - Determinism tests
- suites/routing_authz.yaml - Authorization tests

## Definition of Done

Theme 5 is complete when:

1. ✅ Kernel validates IPC strictly by `shared/contracts/v1/*`
2. ✅ Any schema change without versioning → DENY via `governance/guards/abi_guard`
3. ✅ Intent/policy/invariants validated by `tooling/system_validator`
4. ✅ Compatibility matrices cover all combinations
5. ✅ Fixtures and conformance suites match active version

## Security Invariants

- **NO_BYPASS**: All capability invocations MUST pass through validation
- **DENY_BY_DEFAULT**: Routes are deny-by-default (explicit allowlist)
- **INTERNAL_PROTECTED**: Internal capabilities CANNOT be called directly from UI
- **CHAIN_VALIDATED**: Capability chains MUST be explicitly allowed
- **AUDIT_ALL**: ALL invocations MUST be logged

## Usage

### Validate System Files

```bash
cd tooling/system_validator
cargo run
# Output: dist/reports/system_validation_report.json
```

### Check ABI Changes

```bash
governance/guards/abi_guard
# Runs automatically in CI
```

### Run Conformance Tests

```bash
# Use conformance test runner (to be implemented)
conformance_validator --profile v1 --suite all
```

## Development Workflow

### Adding New Capability

1. Define in module manifest (`extensions/modules/*/manifest.yaml`)
2. Add to capabilities schema validation
3. Update routing if needed (`extensions/routing.yaml`)
4. Add fixtures example (`shared/fixtures/v1/`)
5. Run validation: `tooling/system_validator`

### Breaking Change Process

1. Follow `shared/contracts/lifecycle.yaml` approval process
2. Update `shared/contracts/versions.yaml`
3. Update `shared/compatibility/matrix.yaml`
4. Create v2 directory if major version
5. Update fixtures and conformance tests
6. Notify all stakeholders

### Non-Breaking Change

1. Add optional fields only
2. Update patch/minor version in `versions.yaml`
3. Update fixtures if needed
4. Run conformance tests

## References

- Semantic Versioning: https://semver.org
- RFC 8785 (JSON Canonicalization): https://tools.ietf.org/html/rfc8785
- JSON Schema: https://json-schema.org/draft-07/schema

## License

MIT
