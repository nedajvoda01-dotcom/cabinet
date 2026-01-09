# ABI Version 2 (Future)

This directory is a placeholder for the next major version of the axIOm_mini ABI.

## Status

**NOT ACTIVE** - Version 2 is not yet defined or implemented.

## When to Create v2

Version 2 should only be created when there are breaking changes that cannot be accommodated in v1.x through backwards-compatible extensions.

## Breaking Changes That Would Require v2

Examples of changes that would necessitate v2:
- Fundamental changes to the envelope structure
- Incompatible changes to the message routing model
- Major security model revisions
- Changes that would break all existing v1 clients

## Migration Path

When v2 is created:
1. v1 remains active and supported
2. Deprecation timeline is announced (minimum 12 months)
3. Migration guide is provided
4. Both versions run concurrently during transition period
5. See `shared/contracts/lifecycle.yaml` for full process

## Current Version

The current active version is **v1.0.0**. See `../v1/` for active schemas.

## Compatibility Policy

See `shared/contracts/versions.yaml` for the N/N-1 support policy.
