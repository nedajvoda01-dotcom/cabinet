// Module Status
// Tracks runtime status of modules (facts-only)

use serde::{Serialize, Deserialize};
use std::collections::HashMap;
use std::error::Error;
use std::fs;
use std::time::SystemTime;

#[derive(Debug, Clone, Serialize, Deserialize)]
pub struct ModuleStatus {
    pub module_id: String,
    pub status: String,  // "running", "idle", "error", "stopped"
    pub last_invocation: Option<String>,
    pub invocation_count: u64,
    pub error_count: u64,
    pub avg_execution_time_ms: f64,
    pub last_error: Option<String>,
    pub uptime_seconds: u64,
}

#[derive(Debug, Serialize, Deserialize)]
pub struct RuntimeStatus {
    pub timestamp: String,
    pub kernel_version: String,
    pub modules: HashMap<String, ModuleStatus>,
}

/// Records module invocation
pub fn record_invocation(
    module_id: &str,
    execution_time_ms: u64,
    success: bool,
    error: Option<&str>,
    statuses: &mut HashMap<String, ModuleStatus>,
) {
    let status = statuses.entry(module_id.to_string())
        .or_insert_with(|| ModuleStatus {
            module_id: module_id.to_string(),
            status: "idle".to_string(),
            last_invocation: None,
            invocation_count: 0,
            error_count: 0,
            avg_execution_time_ms: 0.0,
            last_error: None,
            uptime_seconds: 0,
        });
    
    status.invocation_count += 1;
    status.last_invocation = Some(current_timestamp());
    
    if !success {
        status.error_count += 1;
        status.status = "error".to_string();
        status.last_error = error.map(|s| s.to_string());
    } else {
        status.status = "running".to_string();
    }
    
    // Update average execution time
    let count = status.invocation_count as f64;
    status.avg_execution_time_ms = 
        (status.avg_execution_time_ms * (count - 1.0) + execution_time_ms as f64) / count;
}

/// Writes runtime status to file
pub fn write_runtime_status(statuses: &HashMap<String, ModuleStatus>) -> Result<(), Box<dyn Error>> {
    let runtime_status = RuntimeStatus {
        timestamp: current_timestamp(),
        kernel_version: "v1.0.0".to_string(),
        modules: statuses.clone(),
    };
    
    // Write to dist/reports/runtime_status.json
    let output_path = "/home/runner/work/cabinet/cabinet/dist/reports/runtime_status.json";
    let json = serde_json::to_string_pretty(&runtime_status)?;
    fs::write(output_path, json)?;
    
    Ok(())
}

fn current_timestamp() -> String {
    chrono::Utc::now().to_rfc3339()
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_record_invocation_success() {
        let mut statuses = HashMap::new();
        
        record_invocation("storage", 100, true, None, &mut statuses);
        
        let status = statuses.get("storage").unwrap();
        assert_eq!(status.invocation_count, 1);
        assert_eq!(status.error_count, 0);
        assert_eq!(status.avg_execution_time_ms, 100.0);
    }
    
    #[test]
    fn test_record_invocation_error() {
        let mut statuses = HashMap::new();
        
        record_invocation("storage", 50, false, Some("Timeout"), &mut statuses);
        
        let status = statuses.get("storage").unwrap();
        assert_eq!(status.invocation_count, 1);
        assert_eq!(status.error_count, 1);
        assert_eq!(status.status, "error");
        assert_eq!(status.last_error, Some("Timeout".to_string()));
    }
    
    #[test]
    fn test_avg_execution_time() {
        let mut statuses = HashMap::new();
        
        record_invocation("storage", 100, true, None, &mut statuses);
        record_invocation("storage", 200, true, None, &mut statuses);
        
        let status = statuses.get("storage").unwrap();
        assert_eq!(status.invocation_count, 2);
        assert_eq!(status.avg_execution_time_ms, 150.0);
    }
}
