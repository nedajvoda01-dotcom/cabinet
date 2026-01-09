// Authorize Route
// Checks if a route is allowed (deny-by-default)

use super::graph::{Route, RoutingGraph};
use crate::authz::authorize::AuthContext;
use std::error::Error;

/// Authorizes a route: checks if the edge exists in the allowlist and command is allowed
pub fn authorize_route(
    graph: &RoutingGraph,
    from_type: &str,
    from_id: &str,
    to_type: &str,
    to_id: &str,
    capability: &str,
    auth_context: &AuthContext,
    parent_capability: Option<&str>,
) -> Result<(), Box<dyn Error>> {
    // Find matching routes
    let matching_routes = graph.find_routes(from_type, from_id, to_type, to_id, capability);
    
    if matching_routes.is_empty() {
        return Err(format!(
            "ROUTING_DENIED: No route found from {}:{} to {}:{} for capability '{}'",
            from_type, from_id, to_type, to_id, capability
        ).into());
    }
    
    // Check if any route allows this request
    for route in matching_routes {
        if check_route_conditions(route, auth_context).is_ok() {
            // If this is a chained call, verify the chain is allowed
            if let Some(parent_cap) = parent_capability {
                if !graph.is_chain_allowed(parent_cap, capability) {
                    return Err(format!(
                        "ROUTING_DENIED: Capability chain '{}' -> '{}' not allowed",
                        parent_cap, capability
                    ).into());
                }
            }
            
            return Ok(());
        }
    }
    
    Err("ROUTING_DENIED: Route conditions not satisfied".into())
}

/// Checks if route conditions are satisfied
fn check_route_conditions(route: &Route, auth_context: &AuthContext) -> Result<(), Box<dyn Error>> {
    if let Some(conditions) = &route.conditions {
        // Check role requirement
        if let Some(allowed_roles) = &conditions.allowed_roles {
            if !allowed_roles.contains(&auth_context.role) {
                return Err(format!(
                    "ROUTING_DENIED: Role '{}' not allowed for route '{}'",
                    auth_context.role, route.id
                ).into());
            }
        }
        
        // Check scope requirements
        if let Some(required_scopes) = &conditions.required_scopes {
            for required_scope in required_scopes {
                if !auth_context.scopes.contains(required_scope) {
                    return Err(format!(
                        "ROUTING_DENIED: Missing required scope '{}' for route '{}'",
                        required_scope, route.id
                    ).into());
                }
            }
        }
    }
    
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    use super::super::graph::{Route, RouteNode, RouteConditions};
    use std::collections::HashMap;
    
    #[test]
    fn test_authorize_route_success() {
        let graph = RoutingGraph {
            routes: vec![
                Route {
                    id: "test-route".to_string(),
                    from: RouteNode {
                        r#type: "ui".to_string(),
                        id: "main_ui".to_string(),
                        capability: None,
                    },
                    to: RouteNode {
                        r#type: "module".to_string(),
                        id: "storage".to_string(),
                        capability: None,
                    },
                    allowed_capabilities: Some(vec!["storage.listings.create".to_string()]),
                    conditions: Some(RouteConditions {
                        required_scopes: Some(vec!["storage:write".to_string()]),
                        allowed_roles: Some(vec!["admin".to_string()]),
                    }),
                    enabled: true,
                    internal: false,
                }
            ],
            capability_chains: HashMap::new(),
        };
        
        let auth_context = AuthContext {
            actor_id: "user-123".to_string(),
            actor_type: "user".to_string(),
            role: "admin".to_string(),
            scopes: vec!["storage:write".to_string()],
        };
        
        let result = authorize_route(
            &graph,
            "ui",
            "main_ui",
            "module",
            "storage",
            "storage.listings.create",
            &auth_context,
            None,
        );
        
        assert!(result.is_ok());
    }
    
    #[test]
    fn test_authorize_route_no_route() {
        let graph = RoutingGraph {
            routes: vec![],
            capability_chains: HashMap::new(),
        };
        
        let auth_context = AuthContext {
            actor_id: "user-123".to_string(),
            actor_type: "user".to_string(),
            role: "admin".to_string(),
            scopes: vec![],
        };
        
        let result = authorize_route(
            &graph,
            "ui",
            "main_ui",
            "module",
            "storage",
            "storage.listings.create",
            &auth_context,
            None,
        );
        
        assert!(result.is_err());
    }
}
