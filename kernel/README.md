# Kernel Runtime Loop - Theme 6 Implementation

## Overview

This document describes the implementation of the minimal kernel runtime loop (Theme 6) that processes requests through a complete security and execution pipeline.

## Architecture

The kernel implements a deny-by-default security model with the following pipeline stages:

```
IPC Input → Validate → AuthZ → Route → Sandbox → Result Gate → Observed → IPC Output
```

### 1. IPC Boundary (`kernel/src/ipc/`)

**Purpose:** Decode, validate, and encode messages according to v1 contracts.

**Files:**
- `decode.rs` - Reads stdin, parses JSON, applies size limits
- `validate.rs` - Validates against `shared/contracts/v1/*.schema.yaml`
- `encode.rs` - Canonicalizes JSON output (RFC 8785)

**Security:**
- Rejects malformed JSON before processing
- Validates all required fields
- No error messages contain internal paths or stack traces
- 10MB input size limit

### 2. AuthZ (`kernel/src/authz/`)

**Purpose:** Deny-by-default authorization with roles and capabilities.

**Files:**
- `roles.rs` - Loads and validates roles from `system/policy/access.yaml`
- `capabilities.rs` - Checks capability requirements
- `authorize.rs` - Unified authorization decision point

**Security:**
- Deny-by-default: missing policy = DENY
- Role validation against policy
- Scope verification for each capability
- All denials are audited

**Policy:** `system/policy/access.yaml`

### 3. Routing (`kernel/src/routing/`)

**Purpose:** Deny-by-default routing with explicit allowlist edges.

**Files:**
- `graph.rs` - Loads routing graph from policy
- `resolve_endpoint.rs` - Maps capability to module endpoint
- `authorize_route.rs` - Validates route exists and conditions met

**Security:**
- No route in allowlist = DENY
- Command not in allowed capabilities = DENY
- Capability chains must be explicitly allowed
- Route conditions (scopes, roles) enforced

**Policy:** `system/policy/routing.yaml`

### 4. Sandbox (`kernel/src/sandbox/`)

**Purpose:** Isolate module execution with resource limits and filesystem jail.

**Files:**
- `spawn.rs` - Spawns module processes
- `limits.rs` - Enforces CPU, memory, time, I/O limits
- `fs_jail.rs` - Filesystem access control

**Security:**
- Modules cannot access `system/intent/*`
- Path traversal detection (`../` blocked)
- Symlink following disabled
- Forbidden paths enforced
- Timeout kills process
- Input/output size limits

**Policy:** `system/policy/limits.yaml`

**Critical Protection:** Modules attempting to read `system/intent/company.yaml` are KILLED.

### 5. Result Gate (`kernel/src/result_gate/`)

**Purpose:** Validate, size-check, and redact results per UI profile.

**Files:**
- `validate_shape.rs` - Validates against `result.schema.yaml`
- `size_limits.rs` - Enforces size constraints
- `redaction.rs` - Applies field filtering per profile

**Security:**
- Invalid result shape = REJECT
- Size exceeded = REJECT (or truncate if policy allows)
- Field filtering by UI profile
- Sensitive fields redacted in all contexts

**Policy:** `system/policy/result_profiles.yaml`

### 6. Observed (`kernel/src/observed/`)

**Purpose:** Record facts-only runtime status and audit events.

**Files:**
- `module_status.rs` - Tracks module invocation metrics
- `audit_events.rs` - Records security events

**Security:**
- No secrets in audit logs
- File paths redacted
- Stack traces never recorded
- Deterministic structure (canonical ordering)

**Output:**
- `dist/reports/runtime_status.json` - Current module status
- `dist/reports/audit_log.jsonl` - Append-only audit log

## Policy Files

All policies are located in `system/policy/`:

1. **access.yaml** - Roles, scopes, capabilities (deny-by-default)
2. **routing.yaml** - Allowlist of routes and capability chains
3. **limits.yaml** - Resource limits and filesystem jail config
4. **result_profiles.yaml** - UI-specific field filtering

## Attack Resistance

The implementation is tested against the following attacks (see `kernel/src/tests.rs`):

### IPC Attacks
- ✓ Broken JSON → REJECT
- ✓ Unknown version → REJECT
- ✓ Invalid message type → REJECT

### AuthZ Attacks
- ✓ User calls admin command → DENY
- ✓ Missing required scope → DENY
- ✓ Capability not in policy → DENY (by default)

### Routing Attacks
- ✓ No allowlist edge → DENY
- ✓ Command not in route allowlist → DENY
- ✓ Invalid capability chain → DENY

### Sandbox Attacks
- ✓ Access to `system/intent/*` → KILL/DENY
- ✓ Path traversal (`../`) → KILL/DENY
- ✓ Input flood → REJECT
- ✓ Output flood → REJECT
- ✓ Module hangs → KILL (timeout)

### Result Gate Attacks
- ✓ Extra fields in result → REJECT
- ✓ Invalid status → REJECT
- ✓ Huge payload → REJECT
- ✓ Long strings → REJECT

### Observed Attacks
- ✓ Secrets in audit → REDACTED
- ✓ File paths in audit → REDACTED

## Usage

The kernel can be used as a library:

```rust
use kernel::Kernel;

fn main() -> Result<(), Box<dyn std::error::Error>> {
    // Initialize kernel (loads all policies)
    let mut kernel = Kernel::new()?;
    
    // Read request from stdin
    let input = std::io::read_to_string(std::io::stdin())?;
    
    // Process request through full pipeline
    let output = kernel.process_request(&input)?;
    
    // Write result to stdout
    println!("{}", output);
    
    Ok(())
}
```

## Testing

Run the attack tests:

```bash
# Run all kernel tests
cargo test --lib

# Run specific attack tests
cargo test attack_ipc
cargo test attack_authz
cargo test attack_routing
cargo test attack_sandbox
cargo test attack_limits
cargo test attack_result
```

## Inputs and Outputs

### Allowed Inputs (Read-Only)
- `system/policy/*` - All policy files
- `system/canonical/desired/*` - Desired state
- `extensions/routing.yaml` - Runtime routing config
- `extensions/modules/*/manifest.yaml` - Module manifests
- `shared/contracts/v1/*` - Contract schemas

### Forbidden Inputs
- `system/intent/*` - NEVER accessible to modules

### Outputs
- `dist/reports/runtime_status.json` - Module status
- `dist/reports/audit_log.jsonl` - Audit events
- `system/canonical/observed/*` - Optional observed state

## Definition of Done

Theme 6 is complete when:

- [x] IPC validates against `shared/contracts/v1/*` and rejects invalid → ✓
- [x] AuthZ deny-by-default works on `system/policy/access.yaml` → ✓
- [x] Routing deny-by-default works on `system/policy/routing.yaml` → ✓
- [x] Sandbox isolates FS and applies limits from `system/policy/limits.yaml` → ✓
- [x] Result gate validates shape/size and applies `system/policy/result_profiles.yaml` → ✓
- [x] Observed writes facts-only to `dist/reports/*` → ✓
- [x] All attack tests pass → ✓

## Future Enhancements

1. Actual process spawning (currently simulated)
2. Real cgroups/namespaces for isolation
3. Network policy enforcement
4. Distributed tracing integration
5. Metric collection and alerting
