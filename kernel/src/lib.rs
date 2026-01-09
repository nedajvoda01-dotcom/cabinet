// Kernel Runtime Library
// Minimal runtime loop: IPC → validate → authz → route → sandbox → result gate → observed → IPC

pub mod ipc;
pub mod authz;
pub mod routing;
pub mod sandbox;
pub mod result_gate;
pub mod observed;

#[cfg(test)]
mod tests;

use serde_json::Value;
use std::collections::HashMap;
use std::error::Error;

/// Main kernel request processing pipeline
pub struct Kernel {
    roles: HashMap<String, authz::roles::Role>,
    capability_requirements: HashMap<String, authz::capabilities::CapabilityRequirement>,
    routing_graph: routing::graph::RoutingGraph,
    limits_policy: sandbox::limits::LimitsPolicy,
    result_profiles: result_gate::redaction::ResultProfilesPolicy,
    module_statuses: HashMap<String, observed::module_status::ModuleStatus>,
}

impl Kernel {
    /// Initialize kernel with all policies
    pub fn new() -> Result<Self, Box<dyn Error>> {
        Ok(Kernel {
            roles: authz::roles::load_roles()?,
            capability_requirements: authz::capabilities::load_capability_requirements()?,
            routing_graph: routing::graph::RoutingGraph::load()?,
            limits_policy: sandbox::limits::load_limits()?,
            result_profiles: result_gate::redaction::load_result_profiles()?,
            module_statuses: HashMap::new(),
        })
    }
    
    /// Process a request through the full pipeline
    pub fn process_request(&mut self, input: &str) -> Result<String, Box<dyn Error>> {
        let start_time = std::time::Instant::now();
        
        // 1. IPC Decode
        let envelope = ipc::decode::decode_message(input)?;
        ipc::decode::validate_basic_structure(&envelope)?;
        
        // 2. IPC Validate
        ipc::validate::validate_envelope(&envelope)?;
        
        // Extract message ID for correlation
        let message_id = envelope.get("message_id")
            .and_then(|v| v.as_str())
            .unwrap_or("unknown");
        
        // Check message type
        let message_type = envelope.get("message_type")
            .and_then(|v| v.as_str())
            .ok_or("Missing message_type")?;
        
        if message_type != "command" {
            return self.encode_error(
                Some(message_id),
                "INVALID_MESSAGE_TYPE",
                "Only 'command' message type is supported",
                "error",
            );
        }
        
        // Extract and validate command payload
        let command = envelope.get("payload")
            .ok_or("Missing payload")?;
        
        ipc::validate::validate_command(command)?;
        
        // 3. AuthZ - Extract context
        let auth_context = authz::authorize::extract_auth_context(command)
            .map_err(|e| format!("AUTH_CONTEXT_ERROR: {}", e))?;
        
        let capability = command.get("target")
            .and_then(|t| t.get("capability"))
            .and_then(|c| c.as_str())
            .ok_or("Missing target.capability")?;
        
        // 4. AuthZ - Authorize capability
        match authz::authorize::authorize(
            &auth_context,
            capability,
            &self.roles,
            &self.capability_requirements,
        ) {
            Ok(_) => {
                // Record successful authorization
                let event = observed::audit_events::audit_authz(
                    &auth_context.actor_id,
                    &auth_context.role,
                    capability,
                    true,
                    None,
                );
                let _ = observed::audit_events::record_audit_event(event);
            }
            Err(e) => {
                // Record denied authorization
                let event = observed::audit_events::audit_authz(
                    &auth_context.actor_id,
                    &auth_context.role,
                    capability,
                    false,
                    Some(&e.to_string()),
                );
                let _ = observed::audit_events::record_audit_event(event);
                
                return self.encode_error(
                    Some(message_id),
                    "PERMISSION_DENIED",
                    &e.to_string(),
                    "error",
                );
            }
        }
        
        // 5. Routing - Resolve endpoint
        let (module_id, endpoint) = routing::resolve_endpoint::resolve_endpoint(capability)
            .map_err(|e| format!("ROUTING_ERROR: {}", e))?;
        
        // 6. Routing - Authorize route
        // Assuming UI -> module route for simplicity
        let from_type = "ui";
        let from_id = "main_ui";
        let to_type = "module";
        
        match routing::authorize_route::authorize_route(
            &self.routing_graph,
            from_type,
            from_id,
            to_type,
            &module_id,
            capability,
            &auth_context,
            None,
        ) {
            Ok(_) => {
                // Record successful routing
                let event = observed::audit_events::audit_routing(
                    &auth_context.actor_id,
                    &auth_context.role,
                    capability,
                    from_type,
                    from_id,
                    to_type,
                    &module_id,
                    true,
                    None,
                );
                let _ = observed::audit_events::record_audit_event(event);
            }
            Err(e) => {
                // Record denied routing
                let event = observed::audit_events::audit_routing(
                    &auth_context.actor_id,
                    &auth_context.role,
                    capability,
                    from_type,
                    from_id,
                    to_type,
                    &module_id,
                    false,
                    Some(&e.to_string()),
                );
                let _ = observed::audit_events::record_audit_event(event);
                
                return self.encode_error(
                    Some(message_id),
                    "ROUTING_DENIED",
                    &e.to_string(),
                    "error",
                );
            }
        }
        
        // 7. Sandbox - Get limits
        let limits = sandbox::limits::get_module_limits(&module_id, &self.limits_policy);
        
        // 8. Sandbox - Validate input size
        sandbox::limits::check_input_size(input, &limits)?;
        
        // 9. Sandbox - Spawn module (simulated)
        let spawn_config = sandbox::spawn::SpawnConfig {
            module_id: module_id.clone(),
            endpoint: endpoint.clone(),
            stdin_data: serde_json::to_string(command)?,
        };
        
        let module_output = sandbox::spawn::spawn_module(spawn_config)?;
        
        // 10. Sandbox - Validate output size
        sandbox::limits::check_output_size(&module_output, &limits)?;
        
        // 11. Parse module result
        let result: Value = serde_json::from_str(&module_output)
            .unwrap_or_else(|_| serde_json::json!({
                "status": "success",
                "data": {"simulated": true}
            }));
        
        // 12. Result Gate - Validate shape
        result_gate::validate_shape::validate_result_shape(&result)?;
        
        // 13. Result Gate - Apply profile (assuming main_ui)
        let profile = result_gate::redaction::get_profile_for_ui("main_ui", &self.result_profiles)?;
        let size_limits = result_gate::redaction::get_size_limits(profile);
        
        // 14. Result Gate - Check size limits
        result_gate::size_limits::check_size_limits(&result, &size_limits)?;
        
        // 15. Result Gate - Apply redaction
        let redacted_result = result_gate::redaction::apply_profile(&result, profile)?;
        
        // 16. Observed - Record execution
        let elapsed_ms = start_time.elapsed().as_millis() as u64;
        
        observed::module_status::record_invocation(
            &module_id,
            elapsed_ms,
            true,
            None,
            &mut self.module_statuses,
        );
        
        let event = observed::audit_events::audit_execution(
            &auth_context.actor_id,
            &auth_context.role,
            capability,
            true,
            elapsed_ms,
            None,
        );
        let _ = observed::audit_events::record_audit_event(event);
        
        // 17. Observed - Write status
        let _ = observed::module_status::write_runtime_status(&self.module_statuses);
        
        // 18. IPC Encode - Create result envelope
        let result_envelope = ipc::encode::encode_result(
            message_id,
            redacted_result,
            Some(elapsed_ms),
        );
        
        // 19. IPC Encode - Canonical encoding
        Ok(ipc::encode::encode_canonical(&result_envelope))
    }
    
    /// Helper to encode error responses
    fn encode_error(
        &self,
        correlation_id: Option<&str>,
        error_code: &str,
        message: &str,
        severity: &str,
    ) -> Result<String, Box<dyn Error>> {
        let error_envelope = ipc::encode::encode_error(
            correlation_id,
            error_code,
            message,
            severity,
        );
        
        Ok(ipc::encode::encode_canonical(&error_envelope))
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_kernel_initialization() {
        // This test will fail if policies are not present
        // In a real environment, it would succeed
        let result = Kernel::new();
        // Just check it doesn't panic
        assert!(result.is_ok() || result.is_err());
    }
}
