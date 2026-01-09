// Audit Events
// Records security and operational events (facts-only, no secrets)

use serde::{Serialize, Deserialize};
use std::error::Error;
use std::fs::{File, OpenOptions};
use std::io::Write;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AuditEvent {
    pub timestamp: String,
    pub event_type: String,
    pub actor_id: String,
    pub actor_role: String,
    pub capability: String,
    pub result: String,  // "allowed", "denied", "error"
    pub reason: Option<String>,
    pub metadata: Option<AuditMetadata>,
}

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct AuditMetadata {
    pub from_type: Option<String>,
    pub from_id: Option<String>,
    pub to_type: Option<String>,
    pub to_id: Option<String>,
    pub execution_time_ms: Option<u64>,
    pub error_code: Option<String>,
}

/// Records an audit event
pub fn record_audit_event(event: AuditEvent) -> Result<(), Box<dyn Error>> {
    // Redact any sensitive data before writing
    let sanitized = sanitize_event(event);
    
    // Write to dist/reports/audit_log.jsonl (JSON Lines format)
    let output_path = "/home/runner/work/cabinet/cabinet/dist/reports/audit_log.jsonl";
    
    let mut file = OpenOptions::new()
        .create(true)
        .append(true)
        .open(output_path)?;
    
    let json = serde_json::to_string(&sanitized)?;
    writeln!(file, "{}", json)?;
    
    Ok(())
}

/// Sanitizes event to remove any secrets or sensitive data
fn sanitize_event(mut event: AuditEvent) -> AuditEvent {
    // Redact sensitive fields from reason
    if let Some(reason) = &event.reason {
        event.reason = Some(redact_sensitive_content(reason));
    }
    
    // Ensure no paths, stack traces, or secrets in metadata
    if let Some(metadata) = &mut event.metadata {
        if let Some(error_code) = &metadata.error_code {
            // Only keep the error code, not full messages with paths
            metadata.error_code = Some(error_code.split(':').next().unwrap_or(error_code).to_string());
        }
    }
    
    event
}

/// Redacts sensitive content from strings
fn redact_sensitive_content(content: &str) -> String {
    let mut result = content.to_string();
    
    // Redact file paths
    if content.contains("/home/") || content.contains("/mnt/") || content.contains("/etc/") {
        result = "[REDACTED: contains file path]".to_string();
    }
    
    // Redact anything that looks like a token or key
    if content.contains("token") || content.contains("key") || content.contains("secret") {
        result = "[REDACTED: contains sensitive keyword]".to_string();
    }
    
    result
}

/// Creates audit event for authorization check
pub fn audit_authz(
    actor_id: &str,
    actor_role: &str,
    capability: &str,
    allowed: bool,
    reason: Option<&str>,
) -> AuditEvent {
    AuditEvent {
        timestamp: current_timestamp(),
        event_type: "authorization".to_string(),
        actor_id: actor_id.to_string(),
        actor_role: actor_role.to_string(),
        capability: capability.to_string(),
        result: if allowed { "allowed".to_string() } else { "denied".to_string() },
        reason: reason.map(|s| s.to_string()),
        metadata: None,
    }
}

/// Creates audit event for routing check
pub fn audit_routing(
    actor_id: &str,
    actor_role: &str,
    capability: &str,
    from_type: &str,
    from_id: &str,
    to_type: &str,
    to_id: &str,
    allowed: bool,
    reason: Option<&str>,
) -> AuditEvent {
    AuditEvent {
        timestamp: current_timestamp(),
        event_type: "routing".to_string(),
        actor_id: actor_id.to_string(),
        actor_role: actor_role.to_string(),
        capability: capability.to_string(),
        result: if allowed { "allowed".to_string() } else { "denied".to_string() },
        reason: reason.map(|s| s.to_string()),
        metadata: Some(AuditMetadata {
            from_type: Some(from_type.to_string()),
            from_id: Some(from_id.to_string()),
            to_type: Some(to_type.to_string()),
            to_id: Some(to_id.to_string()),
            execution_time_ms: None,
            error_code: None,
        }),
    }
}

/// Creates audit event for execution
pub fn audit_execution(
    actor_id: &str,
    actor_role: &str,
    capability: &str,
    success: bool,
    execution_time_ms: u64,
    error_code: Option<&str>,
) -> AuditEvent {
    AuditEvent {
        timestamp: current_timestamp(),
        event_type: "execution".to_string(),
        actor_id: actor_id.to_string(),
        actor_role: actor_role.to_string(),
        capability: capability.to_string(),
        result: if success { "success".to_string() } else { "error".to_string() },
        reason: None,
        metadata: Some(AuditMetadata {
            from_type: None,
            from_id: None,
            to_type: None,
            to_id: None,
            execution_time_ms: Some(execution_time_ms),
            error_code: error_code.map(|s| s.to_string()),
        }),
    }
}

fn current_timestamp() -> String {
    chrono::Utc::now().to_rfc3339()
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_sanitize_event_with_path() {
        let event = AuditEvent {
            timestamp: "2026-01-09T15:00:00Z".to_string(),
            event_type: "test".to_string(),
            actor_id: "user-123".to_string(),
            actor_role: "admin".to_string(),
            capability: "test.cap".to_string(),
            result: "error".to_string(),
            reason: Some("/home/user/secret/path/file.txt".to_string()),
            metadata: None,
        };
        
        let sanitized = sanitize_event(event);
        assert!(sanitized.reason.unwrap().contains("REDACTED"));
    }
    
    #[test]
    fn test_redact_sensitive_content() {
        assert_eq!(redact_sensitive_content("/home/user/file"), "[REDACTED: contains file path]");
        assert_eq!(redact_sensitive_content("api_key=secret"), "[REDACTED: contains sensitive keyword]");
        assert_eq!(redact_sensitive_content("normal message"), "normal message");
    }
}
