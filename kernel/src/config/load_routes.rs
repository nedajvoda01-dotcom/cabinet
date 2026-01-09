// Routing Configuration Loading
// Validates routing.yaml against shared/contracts/v1/routing.schema.yaml

use std::fs;
use std::path::Path;
use std::error::Error;
use serde_yaml;
use serde_json::Value;

/// Load and validate routing configuration
/// Fails if routing doesn't conform to shared/contracts/v1/routing.schema.yaml
pub fn load_routing_config(routing_path: &Path) -> Result<Value, Box<dyn Error>> {
    if !routing_path.exists() {
        return Err(format!("Routing config not found: {}", routing_path.display()).into());
    }
    
    let content = fs::read_to_string(routing_path)?;
    let routing: Value = serde_yaml::from_str(&content)?;
    
    // Validate routing structure
    validate_routing_config(&routing)?;
    
    Ok(routing)
}

/// Validate routing configuration structure
fn validate_routing_config(routing: &Value) -> Result<(), Box<dyn Error>> {
    // Required fields per routing.schema.yaml
    require_field(routing, "version")?;
    require_field(routing, "edges")?;
    
    // Validate version format
    let version = routing["version"].as_str()
        .ok_or("version must be a string")?;
    if !version.starts_with("v1.") {
        return Err(format!("Unsupported routing version: {}", version).into());
    }
    
    // Validate edges is array
    let edges = routing["edges"].as_array()
        .ok_or("edges must be an array")?;
    
    // Validate each edge
    for (idx, edge) in edges.iter().enumerate() {
        validate_edge(edge, idx)?;
    }
    
    Ok(())
}

/// Validate a single routing edge
fn validate_edge(edge: &Value, idx: usize) -> Result<(), Box<dyn Error>> {
    // Required fields for edge
    require_field(edge, "from_type")?;
    require_field(edge, "from_id")?;
    require_field(edge, "to_type")?;
    require_field(edge, "to_id")?;
    
    // Validate types
    let from_type = edge["from_type"].as_str()
        .ok_or(format!("Edge {}: from_type must be string", idx))?;
    let to_type = edge["to_type"].as_str()
        .ok_or(format!("Edge {}: to_type must be string", idx))?;
    
    // Valid types: ui, module, registry, platform
    for type_val in &[from_type, to_type] {
        match *type_val {
            "ui" | "module" | "registry" | "platform" => {}
            _ => return Err(format!("Edge {}: Invalid type '{}'", idx, type_val).into()),
        }
    }
    
    Ok(())
}

/// Helper to check required field exists
fn require_field(obj: &Value, field: &str) -> Result<(), Box<dyn Error>> {
    if !obj.get(field).is_some() {
        return Err(format!("Missing required field: {}", field).into());
    }
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_validate_routing_config_valid() {
        let routing = json!({
            "version": "v1.0.0",
            "edges": [
                {
                    "from_type": "ui",
                    "from_id": "main_ui",
                    "to_type": "module",
                    "to_id": "backend_ui"
                }
            ]
        });
        
        assert!(validate_routing_config(&routing).is_ok());
    }
    
    #[test]
    fn test_validate_routing_config_missing_edges() {
        let routing = json!({
            "version": "v1.0.0"
        });
        
        assert!(validate_routing_config(&routing).is_err());
    }
    
    #[test]
    fn test_validate_edge_invalid_type() {
        let edge = json!({
            "from_type": "invalid",
            "from_id": "test",
            "to_type": "module",
            "to_id": "test"
        });
        
        assert!(validate_edge(&edge, 0).is_err());
    }
}
