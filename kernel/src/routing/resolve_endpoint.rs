// Resolve Endpoint
// Determines which module should handle a capability

use std::error::Error;
use std::fs;
use serde::Deserialize;

#[derive(Debug, Deserialize)]
pub struct ModuleManifest {
    pub module: ModuleInfo,
    pub capabilities: Vec<CapabilityDef>,
    pub endpoints: Endpoints,
}

#[derive(Debug, Deserialize)]
pub struct ModuleInfo {
    pub id: String,
    pub name: String,
}

#[derive(Debug, Deserialize)]
pub struct CapabilityDef {
    pub id: String,
    pub handler: String,
}

#[derive(Debug, Deserialize)]
pub struct Endpoints {
    pub invoke: String,
    pub health: String,
}

/// Resolves a capability to its module endpoint
pub fn resolve_endpoint(capability: &str) -> Result<(String, String), Box<dyn Error>> {
    // Extract module from capability
    // e.g., "storage.listings.create" -> module could be "storage"
    let module_id = extract_module_from_capability(capability)?;
    
    // Load module manifest
    let manifest = load_module_manifest(&module_id)?;
    
    // Verify capability is provided by this module
    let cap_def = manifest.capabilities.iter()
        .find(|c| c.id == capability)
        .ok_or_else(|| format!("Capability '{}' not found in module '{}'", capability, module_id))?;
    
    Ok((module_id, manifest.endpoints.invoke))
}

/// Extracts likely module ID from capability name
fn extract_module_from_capability(capability: &str) -> Result<String, Box<dyn Error>> {
    // For now, simple heuristic: first part of capability
    // "storage.listings.create" -> "storage"
    // "import.run" -> "storage" (import is part of storage module)
    
    if capability.starts_with("storage.") || capability.starts_with("import.") || capability.starts_with("parser.") {
        return Ok("storage".to_string());
    }
    
    if capability.starts_with("pricing.") {
        return Ok("pricing".to_string());
    }
    
    if capability.starts_with("automation.") || capability.starts_with("workflow.") {
        return Ok("automation".to_string());
    }
    
    Err(format!("Cannot determine module for capability: {}", capability).into())
}

/// Loads a module manifest
fn load_module_manifest(module_id: &str) -> Result<ModuleManifest, Box<dyn Error>> {
    let manifest_path = format!(
        "/home/runner/work/cabinet/cabinet/extensions/modules/{}/manifest.yaml",
        module_id
    );
    
    let content = fs::read_to_string(&manifest_path)
        .map_err(|e| format!("Failed to read manifest for module '{}': {}", module_id, e))?;
    
    let manifest: ModuleManifest = serde_yaml::from_str(&content)
        .map_err(|e| format!("Failed to parse manifest for module '{}': {}", module_id, e))?;
    
    Ok(manifest)
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_extract_module_from_capability() {
        assert_eq!(extract_module_from_capability("storage.listings.create").unwrap(), "storage");
        assert_eq!(extract_module_from_capability("import.run").unwrap(), "storage");
        assert_eq!(extract_module_from_capability("pricing.calculate").unwrap(), "pricing");
    }
}
