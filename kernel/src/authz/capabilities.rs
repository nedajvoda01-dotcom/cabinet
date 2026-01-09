// Capability Checks
// Validates capability requirements from policy

use serde::Deserialize;
use std::collections::HashMap;
use std::error::Error;
use std::fs;

#[derive(Debug, Clone, Deserialize)]
pub struct CapabilityRequirement {
    pub required_scopes: Option<Vec<String>>,
    pub required_roles: Option<Vec<String>>,
}

#[derive(Debug, Deserialize)]
struct AccessPolicy {
    capability_requirements: HashMap<String, CapabilityRequirement>,
}

/// Loads capability requirements from access policy
pub fn load_capability_requirements() -> Result<HashMap<String, CapabilityRequirement>, Box<dyn Error>> {
    let policy_path = "/home/runner/work/cabinet/cabinet/system/policy/access.yaml";
    let content = fs::read_to_string(policy_path)
        .map_err(|e| format!("Failed to read access policy: {}", e))?;
    
    let policy: AccessPolicy = serde_yaml::from_str(&content)
        .map_err(|e| format!("Failed to parse access policy: {}", e))?;
    
    Ok(policy.capability_requirements)
}

/// Checks if a capability can be invoked with the given role
pub fn check_capability_allowed(
    capability: &str,
    role: &str,
    scopes: &[String],
    requirements: &HashMap<String, CapabilityRequirement>,
) -> Result<(), Box<dyn Error>> {
    // Get requirements for this capability
    let req = match requirements.get(capability) {
        Some(r) => r,
        None => {
            // No explicit requirements = deny by default
            return Err(format!("PERMISSION_DENIED: Capability '{}' has no policy (deny-by-default)", capability).into());
        }
    };
    
    // Check role requirement
    if let Some(required_roles) = &req.required_roles {
        if !required_roles.contains(&role.to_string()) {
            return Err(format!(
                "PERMISSION_DENIED: Role '{}' not authorized for capability '{}'",
                role, capability
            ).into());
        }
    }
    
    // Check scope requirements
    if let Some(required_scopes) = &req.required_scopes {
        for required_scope in required_scopes {
            if !scopes.contains(required_scope) {
                return Err(format!(
                    "PERMISSION_DENIED: Missing required scope '{}' for capability '{}'",
                    required_scope, capability
                ).into());
            }
        }
    }
    
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_check_capability_allowed_success() {
        let mut requirements = HashMap::new();
        requirements.insert(
            "storage.listings.create".to_string(),
            CapabilityRequirement {
                required_scopes: Some(vec!["storage:write".to_string()]),
                required_roles: Some(vec!["admin".to_string(), "editor".to_string()]),
            }
        );
        
        let scopes = vec!["storage:write".to_string()];
        let result = check_capability_allowed(
            "storage.listings.create",
            "admin",
            &scopes,
            &requirements
        );
        
        assert!(result.is_ok());
    }
    
    #[test]
    fn test_check_capability_denied_wrong_role() {
        let mut requirements = HashMap::new();
        requirements.insert(
            "storage.listings.create".to_string(),
            CapabilityRequirement {
                required_scopes: Some(vec!["storage:write".to_string()]),
                required_roles: Some(vec!["admin".to_string()]),
            }
        );
        
        let scopes = vec!["storage:write".to_string()];
        let result = check_capability_allowed(
            "storage.listings.create",
            "viewer",
            &scopes,
            &requirements
        );
        
        assert!(result.is_err());
    }
    
    #[test]
    fn test_check_capability_denied_missing_scope() {
        let mut requirements = HashMap::new();
        requirements.insert(
            "storage.listings.create".to_string(),
            CapabilityRequirement {
                required_scopes: Some(vec!["storage:write".to_string()]),
                required_roles: Some(vec!["admin".to_string()]),
            }
        );
        
        let scopes = vec!["storage:read".to_string()];
        let result = check_capability_allowed(
            "storage.listings.create",
            "admin",
            &scopes,
            &requirements
        );
        
        assert!(result.is_err());
    }
}
