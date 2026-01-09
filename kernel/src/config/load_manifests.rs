// Manifest Loading and Validation
// Validates module and UI manifests against shared/contracts/v1/*manifest*.schema.yaml

use std::fs;
use std::path::Path;
use std::error::Error;
use serde_yaml;
use serde_json::Value;

/// Load and validate a module manifest
/// Fails if manifest doesn't conform to shared/contracts/v1/module.manifest.schema.yaml
pub fn load_module_manifest(manifest_path: &Path) -> Result<Value, Box<dyn Error>> {
    if !manifest_path.exists() {
        return Err(format!("Manifest not found: {}", manifest_path.display()).into());
    }
    
    let content = fs::read_to_string(manifest_path)?;
    let manifest: Value = serde_yaml::from_str(&content)?;
    
    // Validate manifest structure
    validate_module_manifest(&manifest)?;
    
    Ok(manifest)
}

/// Load and validate a UI manifest
/// Fails if manifest doesn't conform to shared/contracts/v1/ui.manifest.schema.yaml
pub fn load_ui_manifest(manifest_path: &Path) -> Result<Value, Box<dyn Error>> {
    if !manifest_path.exists() {
        return Err(format!("Manifest not found: {}", manifest_path.display()).into());
    }
    
    let content = fs::read_to_string(manifest_path)?;
    let manifest: Value = serde_yaml::from_str(&content)?;
    
    // Validate manifest structure
    validate_ui_manifest(&manifest)?;
    
    Ok(manifest)
}

/// Validate module manifest structure
fn validate_module_manifest(manifest: &Value) -> Result<(), Box<dyn Error>> {
    // Required fields per module.manifest.schema.yaml
    require_field(manifest, "version")?;
    require_field(manifest, "module_id")?;
    require_field(manifest, "name")?;
    require_field(manifest, "capabilities")?;
    
    // Validate version format
    let version = manifest["version"].as_str()
        .ok_or("version must be a string")?;
    if !version.starts_with("v1.") {
        return Err(format!("Unsupported manifest version: {}", version).into());
    }
    
    // Validate capabilities is array
    if !manifest["capabilities"].is_array() {
        return Err("capabilities must be an array".into());
    }
    
    Ok(())
}

/// Validate UI manifest structure
fn validate_ui_manifest(manifest: &Value) -> Result<(), Box<dyn Error>> {
    // Required fields per ui.manifest.schema.yaml
    require_field(manifest, "version")?;
    require_field(manifest, "ui_id")?;
    require_field(manifest, "name")?;
    require_field(manifest, "type")?;
    
    // Validate version format
    let version = manifest["version"].as_str()
        .ok_or("version must be a string")?;
    if !version.starts_with("v1.") {
        return Err(format!("Unsupported manifest version: {}", version).into());
    }
    
    // Validate UI type
    let ui_type = manifest["type"].as_str()
        .ok_or("type must be a string")?;
    match ui_type {
        "web" | "cli" | "api" => Ok(()),
        _ => Err(format!("Invalid UI type: {}", ui_type).into()),
    }
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
    fn test_validate_module_manifest_valid() {
        let manifest = json!({
            "version": "v1.0.0",
            "module_id": "test_module",
            "name": "Test Module",
            "capabilities": []
        });
        
        assert!(validate_module_manifest(&manifest).is_ok());
    }
    
    #[test]
    fn test_validate_module_manifest_missing_field() {
        let manifest = json!({
            "version": "v1.0.0",
            "module_id": "test_module"
        });
        
        assert!(validate_module_manifest(&manifest).is_err());
    }
    
    #[test]
    fn test_validate_ui_manifest_valid() {
        let manifest = json!({
            "version": "v1.0.0",
            "ui_id": "main_ui",
            "name": "Main UI",
            "type": "web"
        });
        
        assert!(validate_ui_manifest(&manifest).is_ok());
    }
    
    #[test]
    fn test_validate_ui_manifest_invalid_type() {
        let manifest = json!({
            "version": "v1.0.0",
            "ui_id": "main_ui",
            "name": "Main UI",
            "type": "invalid"
        });
        
        assert!(validate_ui_manifest(&manifest).is_err());
    }
}
