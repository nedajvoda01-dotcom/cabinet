// Filesystem Jail
// Enforces filesystem isolation for modules

use serde::Deserialize;
use std::collections::HashSet;
use std::error::Error;
use std::fs;
use std::path::{Path, PathBuf};

#[derive(Debug, Deserialize)]
struct LimitsPolicy {
    filesystem: FilesystemConfig,
}

#[derive(Debug, Deserialize)]
struct FilesystemConfig {
    forbidden_paths: Vec<String>,
    follow_symlinks: bool,
    detect_path_traversal: bool,
    validate_canonical_paths: bool,
}

/// Loads filesystem jail configuration
pub fn load_fs_config() -> Result<FilesystemConfig, Box<dyn Error>> {
    let policy_path = "/home/runner/work/cabinet/cabinet/system/policy/limits.yaml";
    let content = fs::read_to_string(policy_path)
        .map_err(|e| format!("Failed to read limits policy: {}", e))?;
    
    let policy: LimitsPolicy = serde_yaml::from_str(&content)
        .map_err(|e| format!("Failed to parse limits policy: {}", e))?;
    
    Ok(policy.filesystem)
}

/// Validates that a path is allowed for the module
pub fn validate_path(
    path: &str,
    allowed_paths: &[String],
    readonly_paths: &[String],
    config: &FilesystemConfig,
    write_access: bool,
) -> Result<(), Box<dyn Error>> {
    // 1. Check for path traversal
    if config.detect_path_traversal && contains_path_traversal(path) {
        return Err(format!(
            "SECURITY_VIOLATION: Path traversal detected in '{}'",
            path
        ).into());
    }
    
    // 2. Check against forbidden paths
    for forbidden in &config.forbidden_paths {
        if path.starts_with(forbidden) {
            return Err(format!(
                "SECURITY_VIOLATION: Access to forbidden path '{}' denied",
                path
            ).into());
        }
    }
    
    // 3. Canonicalize if required
    let check_path = if config.validate_canonical_paths {
        canonicalize_path(path)?
    } else {
        path.to_string()
    };
    
    // 4. Check if path is in allowed or readonly paths
    let in_allowed = allowed_paths.iter().any(|p| check_path.starts_with(p));
    let in_readonly = readonly_paths.iter().any(|p| check_path.starts_with(p));
    
    if write_access {
        // Write access only allowed in allowed_paths
        if !in_allowed {
            return Err(format!(
                "SECURITY_VIOLATION: Write access to '{}' not allowed",
                path
            ).into());
        }
    } else {
        // Read access allowed in allowed_paths or readonly_paths
        if !in_allowed && !in_readonly {
            return Err(format!(
                "SECURITY_VIOLATION: Read access to '{}' not allowed",
                path
            ).into());
        }
    }
    
    Ok(())
}

/// Detects path traversal attempts (../, /../, etc.)
fn contains_path_traversal(path: &str) -> bool {
    path.contains("../") || path.contains("/..") || path.contains("..\\") || path.contains("\\..")
}

/// Canonicalizes a path (removes . and .. components)
fn canonicalize_path(path: &str) -> Result<String, Box<dyn Error>> {
    // Simple canonicalization without filesystem access
    let mut components = Vec::new();
    
    for component in path.split('/') {
        match component {
            "" | "." => continue,
            ".." => {
                if components.is_empty() {
                    return Err("Invalid path: too many .. components".into());
                }
                components.pop();
            }
            c => components.push(c),
        }
    }
    
    let mut result = String::from("/");
    result.push_str(&components.join("/"));
    Ok(result)
}

/// Checks if a path is attempting to access system/intent (CRITICAL CHECK)
pub fn check_intent_access(path: &str) -> Result<(), Box<dyn Error>> {
    if path.contains("/system/intent/") || path.contains("system/intent/") {
        return Err("SECURITY_VIOLATION: Modules cannot access system/intent".into());
    }
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_contains_path_traversal() {
        assert!(contains_path_traversal("/some/path/../other"));
        assert!(contains_path_traversal("../etc/passwd"));
        assert!(contains_path_traversal("/data/../../etc"));
        assert!(!contains_path_traversal("/some/normal/path"));
    }
    
    #[test]
    fn test_canonicalize_path() {
        assert_eq!(canonicalize_path("/some/./path").unwrap(), "/some/path");
        assert_eq!(canonicalize_path("/some/path/../other").unwrap(), "/some/other");
        assert_eq!(canonicalize_path("/a/b/c/../../d").unwrap(), "/a/d");
    }
    
    #[test]
    fn test_check_intent_access() {
        assert!(check_intent_access("/system/intent/company.yaml").is_err());
        assert!(check_intent_access("system/intent/company.yaml").is_err());
        assert!(check_intent_access("/system/policy/access.yaml").is_ok());
    }
    
    #[test]
    fn test_validate_path() {
        let config = FilesystemConfig {
            forbidden_paths: vec!["/system/intent".to_string()],
            follow_symlinks: false,
            detect_path_traversal: true,
            validate_canonical_paths: true,
        };
        
        let allowed = vec!["/mnt/data/extensions/modules/storage".to_string()];
        let readonly = vec!["/mnt/data/shared".to_string()];
        
        // Should allow read from readonly
        assert!(validate_path("/mnt/data/shared/contracts/v1/command.yaml", &allowed, &readonly, &config, false).is_ok());
        
        // Should deny write to readonly
        assert!(validate_path("/mnt/data/shared/contracts/v1/command.yaml", &allowed, &readonly, &config, true).is_err());
        
        // Should deny access to forbidden path
        assert!(validate_path("/system/intent/company.yaml", &allowed, &readonly, &config, false).is_err());
    }
}
