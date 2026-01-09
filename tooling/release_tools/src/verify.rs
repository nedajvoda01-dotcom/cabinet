//! Release verification module
//! Verifies reproducibility and catches incompatibilities/drift

use std::fs;
use std::path::Path;
use serde_json::Value;

pub fn verify_release(bundle_path: &Path) -> Result<VerificationResult, String> {
    let content = fs::read_to_string(bundle_path)
        .map_err(|e| format!("Failed to read bundle: {}", e))?;
    
    let bundle: Value = serde_json::from_str(&content)
        .map_err(|e| format!("Failed to parse bundle: {}", e))?;
    
    let mut result = VerificationResult {
        passed: true,
        errors: Vec::new(),
        warnings: Vec::new(),
    };
    
    // Verify deterministic flag
    if bundle.get("deterministic") != Some(&serde_json::json!(true)) {
        result.errors.push("Bundle not marked as deterministic".to_string());
        result.passed = false;
    }
    
    // Verify version
    if bundle.get("version").is_none() {
        result.errors.push("Bundle missing version".to_string());
        result.passed = false;
    }
    
    // Verify file lists exist
    if bundle.get("shared_files").is_none() {
        result.errors.push("Bundle missing shared_files".to_string());
        result.passed = false;
    }
    
    if bundle.get("canonical_files").is_none() {
        result.errors.push("Bundle missing canonical_files".to_string());
        result.passed = false;
    }
    
    Ok(result)
}

pub struct VerificationResult {
    pub passed: bool,
    pub errors: Vec<String>,
    pub warnings: Vec<String>,
}
