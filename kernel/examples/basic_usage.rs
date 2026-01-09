// Example: Using the Kernel Runtime Loop
// This demonstrates the full request processing pipeline

use serde_json::json;

fn main() {
    println!("=== Kernel Runtime Loop Example ===\n");
    
    // Example 1: Valid request
    println!("Example 1: Valid admin request");
    let valid_request = json!({
        "version": "v1.0.0",
        "message_id": "550e8400-e29b-41d4-a716-446655440000",
        "timestamp": "2026-01-09T15:00:00Z",
        "message_type": "command",
        "payload": {
            "command_type": "invoke",
            "target": {
                "capability": "storage.listings.create"
            },
            "args": {
                "data": {
                    "brand": "Toyota",
                    "model": "Camry",
                    "year": 2020,
                    "price": 25000
                }
            },
            "context": {
                "actor": {
                    "id": "user-admin",
                    "type": "user",
                    "roles": ["admin"],
                    "scopes": ["storage:write", "storage:read"]
                }
            }
        }
    });
    
    println!("Request: {}", serde_json::to_string_pretty(&valid_request).unwrap());
    println!("Expected: Success (admin can create listings)\n");
    
    println!("=== Pipeline Stages ===\n");
    println!("1. IPC Decode     - Parse and validate JSON structure");
    println!("2. IPC Validate   - Check against contracts (envelope, command)");
    println!("3. AuthZ          - Verify role has capability and required scopes");
    println!("4. Routing        - Check route exists in allowlist");
    println!("5. Sandbox        - Spawn module with limits and FS jail");
    println!("6. Result Gate    - Validate result, check size, apply profile");
    println!("7. Observed       - Record metrics and audit events");
    println!("8. IPC Encode     - Canonical JSON output");
    
    println!("\n=== Security Features ===\n");
    println!("✓ Deny-by-default authorization");
    println!("✓ Deny-by-default routing");
    println!("✓ Filesystem jail (modules cannot access system/intent)");
    println!("✓ Path traversal protection");
    println!("✓ Input/output size limits");
    println!("✓ Timeout enforcement");
    println!("✓ Result validation and redaction");
    println!("✓ Audit logging (facts-only, no secrets)");
}
