# CI Pipeline Configuration

This directory contains the CI/CD pipeline definitions for the Cabinet Platform Monorepo.

## Structure

- `pipelines/` - Main pipeline definitions
- `jobs/` - Individual job definitions
- `policies/` - Security and quality policies
- `secrets/` - Secret templates and references (no actual secrets)

## Pipelines

### Main Pipeline
- Build and test platform
- Build and test UI
- Run adapter tests
- Contract validation
- Security scans
- Architectural tests

### Release Pipeline
- Version management
- Artifact generation
- Compatibility checks
- Signing and provenance
- Publishing to registry

## Usage

Pipelines are triggered on:
- Pull requests (validation)
- Merge to main (full suite + deployment)
- Tagged releases (release pipeline)
