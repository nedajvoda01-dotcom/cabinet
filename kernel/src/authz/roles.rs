// Role Management
// Maps and validates roles from access policy

use serde::{Deserialize, Serialize};
use std::collections::HashMap;
use std::error::Error;
use std::fs;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct Role {
    pub description: String,
    pub scopes: Vec<String>,
    pub capabilities: Option<Vec<String>>,
    pub rate_limit_per_minute: u32,
    pub max_request_size_bytes: u64,
}

#[derive(Debug, Deserialize)]
struct AccessPolicy {
    version: String,
    policy: String,
    roles: HashMap<String, Role>,
}

/// Loads roles from system/policy/access.yaml
pub fn load_roles() -> Result<HashMap<String, Role>, Box<dyn Error>> {
    let policy_path = "/home/runner/work/cabinet/cabinet/system/policy/access.yaml";
    let content = fs::read_to_string(policy_path)
        .map_err(|e| format!("Failed to read access policy: {}", e))?;
    
    let policy: AccessPolicy = serde_yaml::from_str(&content)
        .map_err(|e| format!("Failed to parse access policy: {}", e))?;
    
    // Verify deny-by-default policy
    if policy.policy != "deny_by_default" {
        return Err("Access policy must be deny_by_default".into());
    }
    
    Ok(policy.roles)
}

/// Validates that a role exists and returns it
pub fn get_role(role_name: &str, roles: &HashMap<String, Role>) -> Result<&Role, Box<dyn Error>> {
    roles.get(role_name)
        .ok_or_else(|| format!("Unknown role: {}", role_name).into())
}

/// Checks if a role has a specific scope
pub fn role_has_scope(role: &Role, scope: &str) -> bool {
    role.scopes.iter().any(|s| s == scope)
}

/// Checks if a role has a specific capability (supports wildcard matching)
pub fn role_has_capability(role: &Role, capability: &str) -> bool {
    if let Some(caps) = &role.capabilities {
        for cap in caps {
            if cap == capability {
                return true;
            }
            // Wildcard matching: "storage.*" matches "storage.listings.create"
            if cap.ends_with(".*") {
                let prefix = &cap[..cap.len() - 2];
                if capability.starts_with(prefix) {
                    return true;
                }
            }
        }
    }
    false
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_role_has_scope() {
        let role = Role {
            description: "Test".to_string(),
            scopes: vec!["storage:read".to_string(), "storage:write".to_string()],
            capabilities: None,
            rate_limit_per_minute: 100,
            max_request_size_bytes: 1024,
        };
        
        assert!(role_has_scope(&role, "storage:read"));
        assert!(role_has_scope(&role, "storage:write"));
        assert!(!role_has_scope(&role, "storage:delete"));
    }
    
    #[test]
    fn test_role_has_capability() {
        let role = Role {
            description: "Test".to_string(),
            scopes: vec![],
            capabilities: Some(vec![
                "storage.listings.create".to_string(),
                "storage.*".to_string()
            ]),
            rate_limit_per_minute: 100,
            max_request_size_bytes: 1024,
        };
        
        assert!(role_has_capability(&role, "storage.listings.create"));
        assert!(role_has_capability(&role, "storage.listings.get"));
        assert!(role_has_capability(&role, "storage.imports.register"));
        assert!(!role_has_capability(&role, "pricing.calculate"));
    }
}
