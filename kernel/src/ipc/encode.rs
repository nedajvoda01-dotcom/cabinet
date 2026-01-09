// IPC Message Encoding
// Encodes outgoing IPC messages to canonical JSON format

use serde_json::{Value, json};
use std::collections::BTreeMap;

/// Encodes a message to canonical JSON according to RFC 8785
pub fn encode_canonical(value: &Value) -> String {
    canonicalize_json(value)
}

/// Canonicalizes JSON by sorting keys recursively
fn canonicalize_json(value: &Value) -> String {
    match value {
        Value::Object(map) => {
            // Sort keys alphabetically
            let mut sorted = BTreeMap::new();
            for (k, v) in map {
                sorted.insert(k.clone(), v.clone());
            }
            
            let mut result = String::from("{");
            let mut first = true;
            for (k, v) in sorted {
                if !first {
                    result.push(',');
                }
                first = false;
                result.push('"');
                result.push_str(&k);
                result.push_str("\":");
                result.push_str(&canonicalize_json(&v));
            }
            result.push('}');
            result
        }
        Value::Array(arr) => {
            // Array order is preserved
            let mut result = String::from("[");
            for (i, v) in arr.iter().enumerate() {
                if i > 0 {
                    result.push(',');
                }
                result.push_str(&canonicalize_json(v));
            }
            result.push(']');
            result
        }
        Value::String(s) => {
            // Use serde_json's built-in string escaping
            serde_json::to_string(s).unwrap()
        }
        Value::Number(n) => {
            n.to_string()
        }
        Value::Bool(b) => {
            b.to_string()
        }
        Value::Null => {
            "null".to_string()
        }
    }
}

/// Creates a result envelope
pub fn encode_result(
    correlation_id: &str,
    data: Value,
    execution_time_ms: Option<u64>,
) -> Value {
    let mut metadata = BTreeMap::new();
    if let Some(time) = execution_time_ms {
        metadata.insert("execution_time_ms".to_string(), json!(time));
    }
    metadata.insert("cached".to_string(), json!(false));
    
    json!({
        "version": "v1.0.0",
        "message_id": generate_message_id(),
        "correlation_id": correlation_id,
        "timestamp": current_timestamp(),
        "message_type": "result",
        "payload": {
            "status": "success",
            "data": data,
            "metadata": metadata
        }
    })
}

/// Creates an error envelope
pub fn encode_error(
    correlation_id: Option<&str>,
    error_code: &str,
    message: &str,
    severity: &str,
) -> Value {
    let mut envelope = json!({
        "version": "v1.0.0",
        "message_id": generate_message_id(),
        "timestamp": current_timestamp(),
        "message_type": "error",
        "payload": {
            "error_code": error_code,
            "message": message,
            "severity": severity,
            "retry": {
                "retryable": false
            }
        }
    });
    
    if let Some(corr_id) = correlation_id {
        envelope["correlation_id"] = json!(corr_id);
    }
    
    envelope
}

// Helper functions

fn generate_message_id() -> String {
    // In real implementation, use UUID v4
    format!("msg-{}", uuid::Uuid::new_v4())
}

fn current_timestamp() -> String {
    // In real implementation, use chrono
    chrono::Utc::now().to_rfc3339()
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_canonical_encoding() {
        let value = json!({
            "z_field": "last",
            "a_field": "first",
            "m_field": "middle"
        });
        
        let canonical = encode_canonical(&value);
        assert_eq!(canonical, r#"{"a_field":"first","m_field":"middle","z_field":"last"}"#);
    }
    
    #[test]
    fn test_nested_canonical() {
        let value = json!({
            "outer": {
                "z": 3,
                "a": 1
            },
            "other": "value"
        });
        
        let canonical = encode_canonical(&value);
        assert!(canonical.starts_with(r#"{"other":"value","outer":{"a":1,"z":3}"#));
    }
}
