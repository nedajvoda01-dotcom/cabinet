// IPC Message Validation
// Validates incoming IPC messages against v1 schemas

use serde_json::Value;
use std::error::Error;

/// Validates an IPC envelope according to envelope.schema.yaml
pub fn validate_envelope(envelope: &Value) -> Result<(), Box<dyn Error>> {
    // Check required fields
    require_field(envelope, "version")?;
    require_field(envelope, "message_id")?;
    require_field(envelope, "timestamp")?;
    require_field(envelope, "message_type")?;
    require_field(envelope, "payload")?;
    
    // Validate version format (v1.x.x)
    let version = envelope["version"].as_str()
        .ok_or("version must be a string")?;
    if !version.starts_with("v1.") {
        return Err(format!("Unsupported version: {}", version).into());
    }
    
    // Validate message_id is UUID v4
    let message_id = envelope["message_id"].as_str()
        .ok_or("message_id must be a string")?;
    validate_uuid_v4(message_id)?;
    
    // Validate message_type is valid enum value
    let message_type = envelope["message_type"].as_str()
        .ok_or("message_type must be a string")?;
    validate_message_type(message_type)?;
    
    // Validate timestamp is ISO 8601
    let timestamp = envelope["timestamp"].as_str()
        .ok_or("timestamp must be a string")?;
    validate_iso8601(timestamp)?;
    
    Ok(())
}

/// Validates a command payload according to command.schema.yaml
pub fn validate_command(command: &Value) -> Result<(), Box<dyn Error>> {
    require_field(command, "command_type")?;
    require_field(command, "target")?;
    
    let command_type = command["command_type"].as_str()
        .ok_or("command_type must be a string")?;
    
    match command_type {
        "invoke" | "query" | "subscribe" | "unsubscribe" => Ok(()),
        _ => Err(format!("Invalid command_type: {}", command_type).into()),
    }?;
    
    // Validate target
    let target = &command["target"];
    require_field(target, "capability")?;
    
    let capability = target["capability"].as_str()
        .ok_or("capability must be a string")?;
    validate_capability_id(capability)?;
    
    Ok(())
}

/// Validates a result payload according to result.schema.yaml
pub fn validate_result(result: &Value) -> Result<(), Box<dyn Error>> {
    require_field(result, "status")?;
    require_field(result, "data")?;
    
    let status = result["status"].as_str()
        .ok_or("status must be a string")?;
    
    match status {
        "success" | "partial_success" => Ok(()),
        _ => Err(format!("Invalid status: {}", status).into()),
    }
}

/// Validates an error payload according to error.schema.yaml
pub fn validate_error(error: &Value) -> Result<(), Box<dyn Error>> {
    require_field(error, "error_code")?;
    require_field(error, "message")?;
    require_field(error, "severity")?;
    
    let severity = error["severity"].as_str()
        .ok_or("severity must be a string")?;
    
    match severity {
        "fatal" | "error" | "warning" | "info" => Ok(()),
        _ => Err(format!("Invalid severity: {}", severity).into()),
    }
}

// Helper functions

fn require_field(obj: &Value, field: &str) -> Result<(), Box<dyn Error>> {
    if !obj.get(field).is_some() {
        return Err(format!("Missing required field: {}", field).into());
    }
    Ok(())
}

fn validate_uuid_v4(uuid: &str) -> Result<(), Box<dyn Error>> {
    // Simple UUID v4 validation
    if uuid.len() != 36 {
        return Err("Invalid UUID length".into());
    }
    // Check format: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
    let parts: Vec<&str> = uuid.split('-').collect();
    if parts.len() != 5 {
        return Err("Invalid UUID format".into());
    }
    Ok(())
}

fn validate_message_type(msg_type: &str) -> Result<(), Box<dyn Error>> {
    match msg_type {
        "command" | "result" | "error" | "capability_query" | "capability_response" => Ok(()),
        _ => Err(format!("Invalid message_type: {}", msg_type).into()),
    }
}

fn validate_iso8601(timestamp: &str) -> Result<(), Box<dyn Error>> {
    // Basic ISO 8601 validation
    if !timestamp.contains('T') {
        return Err("Invalid ISO 8601 timestamp".into());
    }
    Ok(())
}

fn validate_capability_id(capability: &str) -> Result<(), Box<dyn Error>> {
    // Capability must be in dot notation: module.resource.action
    if !capability.contains('.') {
        return Err("Capability must be in dot notation".into());
    }
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_valid_envelope() {
        let envelope = json!({
            "version": "v1.0.0",
            "message_id": "550e8400-e29b-41d4-a716-446655440000",
            "timestamp": "2026-01-09T15:00:00Z",
            "message_type": "command",
            "payload": {}
        });
        
        assert!(validate_envelope(&envelope).is_ok());
    }
    
    #[test]
    fn test_missing_version() {
        let envelope = json!({
            "message_id": "550e8400-e29b-41d4-a716-446655440000",
            "timestamp": "2026-01-09T15:00:00Z",
            "message_type": "command",
            "payload": {}
        });
        
        assert!(validate_envelope(&envelope).is_err());
    }
}
