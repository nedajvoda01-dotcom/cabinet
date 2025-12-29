# Delivery & Release Management

This directory contains release and deployment artifacts, policies, and playbooks.

## Structure

- `manifests/` - Artifact manifests and metadata
- `compat/` - Compatibility matrix and checker
- `signing/` - Signing and provenance policies
- `rollout/` - Rollout playbooks and health gates

## Release Process

1. **Version Bump** - Update version in manifests
2. **Build Artifacts** - Generate platform, UI, and adapter artifacts
3. **Compatibility Check** - Verify N/N-1 contract compatibility
4. **Sign Artifacts** - Generate signatures and provenance
5. **Publish** - Upload to registry with metadata
6. **Rollout** - Deploy using rollout playbooks with health gates

## Compatibility

The platform maintains N/N-1 compatibility:
- Current version must work with previous version contracts
- Compatibility checker is a merge blocker
- Breaking changes require explicit approval and migration path

## Artifacts

### Platform
- Backend executable
- Migrations
- Configuration templates

### UI
- Desktop application bundle
- Static assets

### Adapters
- Individual adapter packages
- Health check endpoints
- Descriptor files
