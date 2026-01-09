# ABI Compatibility Rules
# Human-readable breaking change policy

## Versioning Policy

axIOm_mini follows **Semantic Versioning 2.0.0** for ABI versions:

- **MAJOR** version (v1.0.0 → v2.0.0): Breaking changes
- **MINOR** version (v1.0.0 → v1.1.0): Backwards-compatible additions
- **PATCH** version (v1.0.0 → v1.0.1): Backwards-compatible bug fixes

## Breaking vs Non-Breaking Changes

### ❌ Breaking Changes (Require Major Version Bump)

These changes will **break existing clients** and require a new major version:

#### Schema Changes
- ❌ Removing a required field
- ❌ Changing a field from optional to required
- ❌ Renaming a field
- ❌ Changing a field type incompatibly (e.g., string → number)
- ❌ Removing an enum value that is in use
- ❌ Changing validation rules to be more strict
- ❌ Removing a schema entirely

#### Protocol Changes
- ❌ Changing message format or structure
- ❌ Modifying envelope structure
- ❌ Changing error code semantics
- ❌ Removing or changing IPC message types
- ❌ Modifying authentication/authorization requirements

#### API Changes
- ❌ Removing a capability
- ❌ Changing capability signatures
- ❌ Changing error response format
- ❌ Modifying routing rules in breaking ways

### ✅ Non-Breaking Changes (Allow Minor/Patch Version)

These changes are **backwards compatible**:

#### Minor Version (New Features)
- ✅ Adding an optional field
- ✅ Adding a new enum value (if clients handle unknowns)
- ✅ Adding a new schema
- ✅ Adding a new capability
- ✅ Adding new optional parameters
- ✅ Relaxing validation rules
- ✅ Adding new IPC message types
- ✅ Extending error details (keeping existing fields)

#### Patch Version (Bug Fixes)
- ✅ Fixing typos in documentation
- ✅ Clarifying descriptions
- ✅ Adding examples
- ✅ Fixing bugs that don't change API surface
- ✅ Performance improvements
- ✅ Security fixes that don't change API

## Deprecation Process

Before removing anything, follow this process:

1. **Mark as Deprecated** (v1.x.0)
   - Add `deprecated: true` to schema
   - Set `deprecated_since_version`
   - Set `removal_version`
   - Provide `replacement` guidance
   - Update documentation

2. **Announce** (at deprecation time)
   - Release notes
   - Email to maintainers
   - Console warnings when used
   - Update migration guides

3. **Wait** (minimum 12 months)
   - Monitor usage
   - Help users migrate
   - Continue supporting deprecated feature

4. **Remove** (v2.0.0)
   - Remove from next major version
   - Ensure migration guide is complete
   - Verify zero usage (or acceptable risk)

## Client Compatibility Requirements

### Clients MUST
- ✅ Ignore unknown fields (forward compatibility)
- ✅ Handle unknown enum values gracefully
- ✅ Validate responses according to schema
- ✅ Check version compatibility at startup
- ✅ Handle deprecation warnings appropriately

### Clients MUST NOT
- ❌ Depend on undocumented behavior
- ❌ Rely on field ordering
- ❌ Assume specific error messages (use error codes)
- ❌ Use deprecated features in new code

## Server Compatibility Requirements

### Servers MUST
- ✅ Accept messages from v1.x clients
- ✅ Provide clear error messages for version mismatches
- ✅ Support N and N-1 versions concurrently
- ✅ Validate all inputs strictly
- ✅ Emit deprecation warnings

### Servers MUST NOT
- ❌ Break compatibility within same major version
- ❌ Remove support for active versions
- ❌ Silently accept invalid data
- ❌ Skip validation checks

## Compatibility Testing

Every ABI change MUST pass:

1. **Backwards Compatibility Tests**
   - Old clients with new server
   - Test fixtures from previous versions
   
2. **Forwards Compatibility Tests**
   - New clients with old server
   - Ignore unknown fields gracefully

3. **Cross-Version Tests**
   - v1.0 client with v1.1 server
   - v1.1 client with v1.0 server

4. **Conformance Tests**
   - All tests in `shared/conformance/`
   - Schema validation
   - Round-trip encoding/decoding

## Version Negotiation

1. Client sends version in `envelope.version`
2. Server checks compatibility matrix
3. If compatible: proceed
4. If incompatible: return VERSION_MISMATCH error
5. Client should upgrade or use compatible version

## Examples

### ✅ Safe Changes

```yaml
# Adding optional field (v1.1.0)
properties:
  existing_field:
    type: string
  new_optional_field:  # NEW
    type: string

# Adding enum value (v1.1.0)
enum:
  - existing_value
  - new_value  # NEW
```

### ❌ Unsafe Changes

```yaml
# DON'T: Removing field (requires v2.0.0)
properties:
  old_field:  # REMOVED - BREAKING!
    type: string

# DON'T: Changing field type (requires v2.0.0)
properties:
  my_field:
    type: number  # Was string - BREAKING!
```

### 🔄 Safe Deprecation

```yaml
# Step 1: Deprecate (v1.5.0)
properties:
  old_field:
    type: string
    deprecated: true
    deprecated_since_version: "v1.5.0"
    removal_version: "v2.0.0"
    replacement: "new_field"
  new_field:  # Replacement
    type: string

# Step 2: Wait 12 months

# Step 3: Remove (v2.0.0)
properties:
  new_field:
    type: string
  # old_field removed
```

## Governance

All breaking changes MUST be approved by:
- Architecture review board
- Security team
- Affected module maintainers

See `shared/contracts/lifecycle.yaml` for detailed approval process.

## References

- Full lifecycle: `shared/contracts/lifecycle.yaml`
- Version history: `shared/contracts/versions.yaml`
- Compatibility matrix: `shared/compatibility/matrix.yaml`
- Conformance tests: `shared/conformance/`
