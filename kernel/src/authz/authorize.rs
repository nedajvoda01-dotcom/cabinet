// Authorization
// Unified authorization check point (deny-by-default)

use super::roles::{self, Role};
use super::capabilities;
use std::collections::HashMap;
use std::error::Error;

pub struct AuthContext {
    pub actor_id: String,
    pub actor_type: String,
    pub role: String,
    pub scopes: Vec<String>,
}

/// Main authorization check: can this actor invoke this capability?
/// Returns Ok(()) if allowed, Err with PERMISSION_DENIED otherwise
pub fn authorize(
    context: &AuthContext,
    capability: &str,
    roles_map: &HashMap<String, Role>,
    capability_requirements: &HashMap<String, capabilities::CapabilityRequirement>,
) -> Result<(), Box<dyn Error>> {
    // 1. Validate role exists
    let role = roles::get_role(&context.role, roles_map)?;
    
    // 2. Verify role has the capability
    if !roles::role_has_capability(role, capability) {
        return Err(format!(
            "PERMISSION_DENIED: Role '{}' does not have capability '{}'",
            context.role, capability
        ).into());
    }
    
    // 3. Check capability-specific requirements
    capabilities::check_capability_allowed(
        capability,
        &context.role,
        &context.scopes,
        capability_requirements,
    )?;
    
    Ok(())
}

/// Extract authorization context from command payload
pub fn extract_auth_context(command: &serde_json::Value) -> Result<AuthContext, Box<dyn Error>> {
    let context_obj = command.get("context")
        .ok_or("Missing context field in command")?;
    
    let actor = context_obj.get("actor")
        .ok_or("Missing actor in context")?;
    
    let actor_id = actor.get("id")
        .and_then(|v| v.as_str())
        .ok_or("Missing or invalid actor.id")?
        .to_string();
    
    let actor_type = actor.get("type")
        .and_then(|v| v.as_str())
        .ok_or("Missing or invalid actor.type")?
        .to_string();
    
    // Extract roles array (take first role for simplicity)
    let roles = actor.get("roles")
        .and_then(|v| v.as_array())
        .ok_or("Missing or invalid actor.roles")?;
    
    let role = roles.get(0)
        .and_then(|v| v.as_str())
        .ok_or("No roles specified for actor")?
        .to_string();
    
    // Extract scopes
    let scopes = actor.get("scopes")
        .and_then(|v| v.as_array())
        .map(|arr| {
            arr.iter()
                .filter_map(|v| v.as_str())
                .map(|s| s.to_string())
                .collect()
        })
        .unwrap_or_else(Vec::new);
    
    Ok(AuthContext {
        actor_id,
        actor_type,
        role,
        scopes,
    })
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_extract_auth_context() {
        let command = json!({
            "command_type": "invoke",
            "target": {
                "capability": "storage.listings.create"
            },
            "context": {
                "actor": {
                    "id": "user-123",
                    "type": "user",
                    "roles": ["admin"],
                    "scopes": ["storage:write", "storage:read"]
                }
            }
        });
        
        let context = extract_auth_context(&command).unwrap();
        assert_eq!(context.actor_id, "user-123");
        assert_eq!(context.actor_type, "user");
        assert_eq!(context.role, "admin");
        assert_eq!(context.scopes.len(), 2);
    }
}
