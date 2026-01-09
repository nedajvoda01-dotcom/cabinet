# Tooling Pipeline

Deterministic, reproducible tooling for the Cabinet platform.

## Overview

This directory contains 6 tools that form the deterministic pipeline for managing system configuration:

1. **system_validator** - Validates system/ data against schemas and invariants
2. **canonicalizer** - Produces deterministic YAML/JSON formatting
3. **desired_builder** - Builds desired state from intent
4. **diff_builder** - Compares desired vs observed state
5. **registry_builder** - Builds read-model registry
6. **release_tools** - Creates reproducible release bundles

## Building

Build all tools:

```bash
cd tooling
for dir in */; do
  cd "$dir"
  cargo build --release
  cd ..
done
```

Binaries will be in `<tool>/target/release/`

## Running the Pipeline

### 1. Validate System Configuration

```bash
./tooling/system_validator/target/release/system_validator
```

**Inputs:** system/intent/, system/policy/, system/invariants/, shared/schemas/
**Outputs:** dist/reports/system_validation_report.json
**Exit:** 0 on success, 1 on validation errors (blocks pipeline)

### 2. Canonicalize Files (Optional)

```bash
# To dist/canonicalized/
./tooling/canonicalizer/target/release/canonicalizer system/intent

# In-place rewrite
./tooling/canonicalizer/target/release/canonicalizer --rewrite system/intent/company.intent.yaml
```

**Inputs:** Any YAML/JSON files
**Outputs:** dist/canonicalized/* (or in-place with --rewrite)

### 3. Build Desired State

```bash
./tooling/desired_builder/target/release/desired_builder
```

**Inputs:** system/intent/**
**Outputs:** 
- system/canonical/desired/*.yaml
- dist/reports/desired_build_report.json

### 4. Build Diff

```bash
./tooling/diff_builder/target/release/diff_builder
```

**Inputs:** 
- system/canonical/desired/*.yaml
- system/canonical/observed/*.yaml

**Outputs:**
- system/canonical/diff/*.yaml
- dist/reports/diff_report.json

### 5. Build Registry (Optional)

```bash
./tooling/registry_builder/target/release/registry_builder
```

**Inputs:** system/canonical/*, system/policy/*
**Outputs:**
- system/registry/**
- dist/reports/registry_report.json

⚠️ **Note:** Registry is a READ-MODEL only. Source of truth is intent→desired.

### 6. Create Release Bundle

```bash
# Create bundle
./tooling/release_tools/target/release/release_tools bundle

# Verify bundle
./tooling/release_tools/target/release/release_tools verify
```

**Inputs:** shared/, system/canonical/
**Outputs:**
- dist/releases/release_bundle.json
- dist/reports/release_verify_report.json

## Full Pipeline Script

```bash
#!/bin/bash
set -e

echo "=== Cabinet Tooling Pipeline ==="

echo "1. Validating system configuration..."
./tooling/system_validator/target/release/system_validator || exit 1

echo "2. Building desired state..."
./tooling/desired_builder/target/release/desired_builder || exit 1

echo "3. Building diff..."
./tooling/diff_builder/target/release/diff_builder || exit 1

echo "4. Building registry..."
./tooling/registry_builder/target/release/registry_builder || exit 1

echo "5. Creating release bundle..."
./tooling/release_tools/target/release/release_tools bundle || exit 1

echo "6. Verifying release..."
./tooling/release_tools/target/release/release_tools verify || exit 1

echo "✅ Pipeline complete!"
```

## Key Principles

### Determinism
All tools produce **identical output** for identical input:
- Same timestamps (Unix epoch)
- Same key ordering (BTreeMap)
- No external dependencies (except explicitly allowed)

### Prohibited Actions

Tools MUST NOT:
- Read from extensions/** (not canonical)
- Write to system/intent/** (SSOT is read-only)
- Generate non-deterministic output
- Perform "smart magic" (only explicit transformations)

### SSOT Hierarchy

```
intent/          → SSOT (human-authored)
  ↓
canonical/desired/ → derived (deterministic)
  ↓
registry/        → read-model (convenience)
```

Conflicts are resolved by updating intent, never by modifying derived state.

## Reports

All tools write JSON reports to `dist/reports/`:

- `system_validation_report.json` - Validation results
- `desired_build_report.json` - Desired state build status
- `diff_report.json` - Diff summary
- `registry_report.json` - Registry build status
- `release_verify_report.json` - Release verification

Reports include:
- Status (PASS/FAIL/SUCCESS)
- Errors and warnings
- File counts and processing stats
- Timestamps (deterministic)

## Development

### Adding a New Tool

1. Create directory: `tooling/my_tool/`
2. Add `Cargo.toml` with dependencies
3. Implement `src/main.rs` following determinism principles
4. Document inputs/outputs
5. Update this README
6. Update `architecture/LIVING STRUCTURE CANON.txt`

### Testing

Each tool should:
- Exit 0 on success, 1 on failure
- Write detailed report to dist/reports/
- Be idempotent (run multiple times = same result)
- Handle missing inputs gracefully

## Architecture

See `architecture/LIVING STRUCTURE CANON.txt` for complete structural documentation.
