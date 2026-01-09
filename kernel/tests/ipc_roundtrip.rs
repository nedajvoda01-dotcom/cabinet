// IPC Roundtrip Integration Tests
// Tests that messages pass through kernel pipeline correctly
// Based on shared/conformance/suites/invoke_roundtrip.yaml

use serde_json::json;

#[test]
fn test_canonical_json_vectors() {
    // Load and test vectors from shared/test_vectors/canonical_json/vectors.yaml
    // This test ensures kernel's encode follows canonical rules
    
    use kernel::ipc::encode::encode_canonical;
    
    // Test vector canonical-001: Simple object with keys in non-alphabetical order
    let input = json!({
        "z_field": "last",
        "a_field": "first",
        "m_field": "middle"
    });
    
    let canonical = encode_canonical(&input);
    assert_eq!(
        canonical,
        r#"{"a_field":"first","m_field":"middle","z_field":"last"}"#,
        "Keys must be sorted alphabetically"
    );
}

#[test]
fn test_canonical_nested_objects() {
    use kernel::ipc::encode::encode_canonical;
    
    // Test vector canonical-002: Nested objects
    let input = json!({
        "outer": {
            "z": 3,
            "a": 1,
            "m": 2
        },
        "other": "value"
    });
    
    let canonical = encode_canonical(&input);
    assert_eq!(
        canonical,
        r#"{"other":"value","outer":{"a":1,"m":2,"z":3}}"#,
        "All nesting levels must be sorted"
    );
}

#[test]
fn test_canonical_no_whitespace() {
    use kernel::ipc::encode::encode_canonical;
    
    // Test vector canonical-007: No extra whitespace
    let input = json!({
        "key": "value"
    });
    
    let canonical = encode_canonical(&input);
    assert_eq!(
        canonical,
        r#"{"key":"value"}"#,
        "No spaces, newlines, or indentation"
    );
    assert!(!canonical.contains(' '), "No spaces in canonical JSON");
    assert!(!canonical.contains('\n'), "No newlines in canonical JSON");
}

#[test]
fn test_canonical_booleans() {
    use kernel::ipc::encode::encode_canonical;
    
    // Test vector canonical-008: Boolean values
    let input = json!({
        "true_val": true,
        "false_val": false
    });
    
    let canonical = encode_canonical(&input);
    assert_eq!(
        canonical,
        r#"{"false_val":false,"true_val":true}"#,
        "Booleans as lowercase true/false"
    );
}

#[test]
fn test_canonical_arrays_preserve_order() {
    use kernel::ipc::encode::encode_canonical;
    
    // Test vector canonical-004: Arrays preserve order
    let input = json!({
        "items": [3, 1, 2]
    });
    
    let canonical = encode_canonical(&input);
    assert_eq!(
        canonical,
        r#"{"items":[3,1,2]}"#,
        "Array order must be preserved"
    );
}

#[test]
fn test_canonical_empty_values() {
    use kernel::ipc::encode::encode_canonical;
    
    // Test vector canonical-006: Empty values and null
    let input = json!({
        "empty_string": "",
        "empty_object": {},
        "empty_array": [],
        "null_value": null
    });
    
    let canonical = encode_canonical(&input);
    assert_eq!(
        canonical,
        r#"{"empty_array":[],"empty_object":{},"empty_string":"","null_value":null}"#,
        "Empty values must be included"
    );
}

#[test]
fn test_ipc_envelope_canonical() {
    use kernel::ipc::encode::encode_canonical;
    
    // Test vector canonical-009: Complete IPC envelope
    let input = json!({
        "version": "v1.0.0",
        "message_type": "command",
        "message_id": "550e8400-e29b-41d4-a716-446655440000",
        "timestamp": "2026-01-09T15:00:00Z",
        "payload": {
            "command_type": "invoke",
            "target": {
                "capability": "storage.create"
            }
        }
    });
    
    let canonical = encode_canonical(&input);
    assert_eq!(
        canonical,
        r#"{"message_id":"550e8400-e29b-41d4-a716-446655440000","message_type":"command","payload":{"command_type":"invoke","target":{"capability":"storage.create"}},"timestamp":"2026-01-09T15:00:00Z","version":"v1.0.0"}"#,
        "Real-world envelope canonicalization"
    );
}

#[test]
fn test_canonical_determinism() {
    use kernel::ipc::encode::encode_canonical;
    
    // Run twice with same input - must produce identical output
    let input = json!({
        "z": 3,
        "a": 1,
        "nested": {
            "y": 2,
            "x": 1
        }
    });
    
    let canonical1 = encode_canonical(&input);
    let canonical2 = encode_canonical(&input);
    
    assert_eq!(canonical1, canonical2, "Canonicalization must be deterministic");
}
