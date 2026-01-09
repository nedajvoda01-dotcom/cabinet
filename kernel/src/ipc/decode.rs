// IPC Message Decoding
// Reads from stdin and parses IPC messages

use serde_json::Value;
use std::error::Error;
use std::io::{self, Read};

/// Reads and decodes an IPC message from stdin
/// Returns: parsed JSON Value or error
pub fn read_stdin() -> Result<Value, Box<dyn Error>> {
    let mut buffer = String::new();
    io::stdin().read_to_string(&mut buffer)?;
    
    // Protect against excessively large inputs
    if buffer.len() > 10 * 1024 * 1024 {  // 10MB limit
        return Err("Input exceeds maximum size (10MB)".into());
    }
    
    decode_message(&buffer)
}

/// Decodes a JSON message with basic protection
pub fn decode_message(input: &str) -> Result<Value, Box<dyn Error>> {
    // Trim whitespace
    let trimmed = input.trim();
    
    // Basic validation before parsing
    if trimmed.is_empty() {
        return Err("Empty input".into());
    }
    
    if !trimmed.starts_with('{') && !trimmed.starts_with('[') {
        return Err("Invalid JSON: must start with { or [".into());
    }
    
    // Parse JSON
    match serde_json::from_str(trimmed) {
        Ok(value) => Ok(value),
        Err(e) => {
            // Don't expose parser internals in error
            Err(format!("Invalid JSON format: parse error").into())
        }
    }
}

/// Validates basic envelope structure before detailed validation
pub fn validate_basic_structure(envelope: &Value) -> Result<(), Box<dyn Error>> {
    if !envelope.is_object() {
        return Err("Envelope must be a JSON object".into());
    }
    
    // Check for required top-level fields
    let required_fields = ["version", "message_id", "timestamp", "message_type", "payload"];
    for field in &required_fields {
        if !envelope.get(field).is_some() {
            return Err(format!("Missing required field: {}", field).into());
        }
    }
    
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_decode_valid_json() {
        let input = r#"{"version": "v1.0.0", "test": true}"#;
        let result = decode_message(input);
        assert!(result.is_ok());
    }
    
    #[test]
    fn test_decode_invalid_json() {
        let input = r#"{"version": "v1.0.0", "test": true"#;  // Missing closing brace
        let result = decode_message(input);
        assert!(result.is_err());
    }
    
    #[test]
    fn test_decode_empty_input() {
        let input = "";
        let result = decode_message(input);
        assert!(result.is_err());
    }
    
    #[test]
    fn test_decode_non_json() {
        let input = "this is not json";
        let result = decode_message(input);
        assert!(result.is_err());
    }
    
    #[test]
    fn test_validate_basic_structure() {
        let envelope = json!({
            "version": "v1.0.0",
            "message_id": "test-123",
            "timestamp": "2026-01-09T15:00:00Z",
            "message_type": "command",
            "payload": {}
        });
        
        assert!(validate_basic_structure(&envelope).is_ok());
    }
    
    #[test]
    fn test_validate_basic_structure_missing_field() {
        let envelope = json!({
            "version": "v1.0.0",
            "message_id": "test-123"
        });
        
        assert!(validate_basic_structure(&envelope).is_err());
    }
}
