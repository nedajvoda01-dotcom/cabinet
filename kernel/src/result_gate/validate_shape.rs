// Validate Result Shape
// Validates result against result.schema.yaml

use serde_json::Value;
use std::error::Error;

/// Validates a result against the schema requirements
pub fn validate_result_shape(result: &Value) -> Result<(), Box<dyn Error>> {
    // Must be an object
    if !result.is_object() {
        return Err("Result must be a JSON object".into());
    }
    
    // Check required fields
    require_field(result, "status")?;
    require_field(result, "data")?;
    
    // Validate status
    let status = result["status"].as_str()
        .ok_or("status must be a string")?;
    
    match status {
        "success" | "partial_success" => Ok(()),
        _ => Err(format!("Invalid status: '{}' (must be 'success' or 'partial_success')", status).into()),
    }?;
    
    // Validate metadata if present
    if let Some(metadata) = result.get("metadata") {
        validate_metadata(metadata)?;
    }
    
    // Validate links if present
    if let Some(links) = result.get("links") {
        validate_links(links)?;
    }
    
    // Check for unknown fields (additionalProperties: false)
    let allowed_fields = ["status", "data", "metadata", "links"];
    for key in result.as_object().unwrap().keys() {
        if !allowed_fields.contains(&key.as_str()) {
            return Err(format!("Unknown field in result: '{}'", key).into());
        }
    }
    
    Ok(())
}

fn require_field(obj: &Value, field: &str) -> Result<(), Box<dyn Error>> {
    if !obj.get(field).is_some() {
        return Err(format!("Missing required field: {}", field).into());
    }
    Ok(())
}

fn validate_metadata(metadata: &Value) -> Result<(), Box<dyn Error>> {
    if !metadata.is_object() {
        return Err("metadata must be an object".into());
    }
    
    // Validate specific fields if present
    if let Some(exec_time) = metadata.get("execution_time_ms") {
        if !exec_time.is_number() {
            return Err("execution_time_ms must be a number".into());
        }
    }
    
    if let Some(cached) = metadata.get("cached") {
        if !cached.is_boolean() {
            return Err("cached must be a boolean".into());
        }
    }
    
    if let Some(warnings) = metadata.get("warnings") {
        if !warnings.is_array() {
            return Err("warnings must be an array".into());
        }
        for warning in warnings.as_array().unwrap() {
            validate_warning(warning)?;
        }
    }
    
    Ok(())
}

fn validate_warning(warning: &Value) -> Result<(), Box<dyn Error>> {
    if !warning.is_object() {
        return Err("Warning must be an object".into());
    }
    
    require_field(warning, "code")?;
    require_field(warning, "message")?;
    
    Ok(())
}

fn validate_links(links: &Value) -> Result<(), Box<dyn Error>> {
    if !links.is_object() {
        return Err("links must be an object".into());
    }
    
    for (_key, link) in links.as_object().unwrap() {
        if !link.is_object() {
            return Err("Each link must be an object".into());
        }
        require_field(link, "href")?;
    }
    
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_valid_result() {
        let result = json!({
            "status": "success",
            "data": {
                "id": "123",
                "name": "Test"
            },
            "metadata": {
                "execution_time_ms": 45,
                "cached": false
            }
        });
        
        assert!(validate_result_shape(&result).is_ok());
    }
    
    #[test]
    fn test_missing_required_field() {
        let result = json!({
            "status": "success"
        });
        
        assert!(validate_result_shape(&result).is_err());
    }
    
    #[test]
    fn test_invalid_status() {
        let result = json!({
            "status": "invalid_status",
            "data": {}
        });
        
        assert!(validate_result_shape(&result).is_err());
    }
    
    #[test]
    fn test_unknown_field() {
        let result = json!({
            "status": "success",
            "data": {},
            "unknown_field": "value"
        });
        
        assert!(validate_result_shape(&result).is_err());
    }
}
