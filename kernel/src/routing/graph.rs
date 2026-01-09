// Routing Graph
// Loads and manages routing allowlist from policy

use serde::Deserialize;
use std::collections::HashMap;
use std::error::Error;
use std::fs;

#[derive(Debug, Clone, Deserialize)]
pub struct Route {
    pub id: String,
    pub from: RouteNode,
    pub to: RouteNode,
    pub allowed_capabilities: Option<Vec<String>>,
    pub conditions: Option<RouteConditions>,
    pub enabled: bool,
    #[serde(default)]
    pub internal: bool,
}

#[derive(Debug, Clone, Deserialize)]
pub struct RouteNode {
    pub r#type: String,
    pub id: String,
    pub capability: Option<String>,
}

#[derive(Debug, Clone, Deserialize)]
pub struct RouteConditions {
    pub required_scopes: Option<Vec<String>>,
    pub allowed_roles: Option<Vec<String>>,
}

#[derive(Debug, Deserialize)]
struct RoutingPolicy {
    version: String,
    policy: String,
    routes: Vec<Route>,
    capability_chains: Option<HashMap<String, Vec<String>>>,
}

pub struct RoutingGraph {
    pub routes: Vec<Route>,
    pub capability_chains: HashMap<String, Vec<String>>,
}

impl RoutingGraph {
    /// Loads routing policy from system/policy/routing.yaml
    pub fn load() -> Result<Self, Box<dyn Error>> {
        let policy_path = "/home/runner/work/cabinet/cabinet/system/policy/routing.yaml";
        let content = fs::read_to_string(policy_path)
            .map_err(|e| format!("Failed to read routing policy: {}", e))?;
        
        let policy: RoutingPolicy = serde_yaml::from_str(&content)
            .map_err(|e| format!("Failed to parse routing policy: {}", e))?;
        
        // Verify deny-by-default policy
        if policy.policy != "deny_by_default" {
            return Err("Routing policy must be deny_by_default".into());
        }
        
        Ok(RoutingGraph {
            routes: policy.routes,
            capability_chains: policy.capability_chains.unwrap_or_default(),
        })
    }
    
    /// Finds routes that match the given from/to criteria
    pub fn find_routes(
        &self,
        from_type: &str,
        from_id: &str,
        to_type: &str,
        to_id: &str,
        capability: &str,
    ) -> Vec<&Route> {
        self.routes.iter()
            .filter(|r| {
                r.enabled &&
                r.from.r#type == from_type &&
                r.from.id == from_id &&
                r.to.r#type == to_type &&
                r.to.id == to_id &&
                self.capability_matches(r, capability)
            })
            .collect()
    }
    
    /// Checks if a capability matches a route's allowed capabilities
    fn capability_matches(&self, route: &Route, capability: &str) -> bool {
        if let Some(allowed) = &route.allowed_capabilities {
            for pattern in allowed {
                if pattern == capability {
                    return true;
                }
                // Wildcard matching: "storage.listings.*" matches "storage.listings.create"
                if pattern.ends_with("*") {
                    let prefix = &pattern[..pattern.len() - 1];
                    if capability.starts_with(prefix) {
                        return true;
                    }
                }
            }
            false
        } else {
            // No restrictions = matches any capability
            true
        }
    }
    
    /// Checks if a capability chain is allowed
    pub fn is_chain_allowed(&self, parent_capability: &str, child_capability: &str) -> bool {
        if let Some(allowed_children) = self.capability_chains.get(parent_capability) {
            allowed_children.contains(&child_capability.to_string())
        } else {
            false
        }
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_capability_pattern_matching() {
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
                    allowed_capabilities: Some(vec!["storage.listings.*".to_string()]),
                    conditions: None,
                    enabled: true,
                    internal: false,
                }
            ],
            capability_chains: HashMap::new(),
        };
        
        assert!(graph.capability_matches(&graph.routes[0], "storage.listings.create"));
        assert!(graph.capability_matches(&graph.routes[0], "storage.listings.get"));
        assert!(!graph.capability_matches(&graph.routes[0], "storage.imports.register"));
    }
}
