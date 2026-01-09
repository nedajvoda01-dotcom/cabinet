// Resource Limits
// Enforces CPU, memory, time, and output limits on modules

use serde::Deserialize;
use std::collections::HashMap;
use std::error::Error;
use std::fs;
use std::time::Duration;

#[derive(Debug, Clone, Deserialize)]
pub struct ModuleLimits {
    pub timeout_ms: u64,
    pub max_memory_mb: u64,
    pub max_cpu_percent: u32,
    pub max_output_bytes: u64,
    pub max_input_bytes: u64,
    pub allowed_file_paths: Option<Vec<String>>,
    pub readonly_paths: Option<Vec<String>>,
}

#[derive(Debug, Deserialize)]
pub struct LimitsPolicy {
    pub defaults: ModuleLimits,
    pub module_limits: HashMap<String, ModuleLimits>,
}

/// Loads limits from system/policy/limits.yaml
pub fn load_limits() -> Result<LimitsPolicy, Box<dyn Error>> {
    let policy_path = "/home/runner/work/cabinet/cabinet/system/policy/limits.yaml";
    let content = fs::read_to_string(policy_path)
        .map_err(|e| format!("Failed to read limits policy: {}", e))?;
    
    let policy: LimitsPolicy = serde_yaml::from_str(&content)
        .map_err(|e| format!("Failed to parse limits policy: {}", e))?;
    
    Ok(policy)
}

/// Gets limits for a specific module
pub fn get_module_limits(module_id: &str, policy: &LimitsPolicy) -> ModuleLimits {
    policy.module_limits.get(module_id)
        .cloned()
        .unwrap_or_else(|| policy.defaults.clone())
}

/// Validates input size against limits
pub fn check_input_size(input: &str, limits: &ModuleLimits) -> Result<(), Box<dyn Error>> {
    let size = input.len() as u64;
    if size > limits.max_input_bytes {
        return Err(format!(
            "LIMIT_EXCEEDED: Input size {} bytes exceeds limit {} bytes",
            size, limits.max_input_bytes
        ).into());
    }
    Ok(())
}

/// Validates output size against limits
pub fn check_output_size(output: &str, limits: &ModuleLimits) -> Result<(), Box<dyn Error>> {
    let size = output.len() as u64;
    if size > limits.max_output_bytes {
        return Err(format!(
            "LIMIT_EXCEEDED: Output size {} bytes exceeds limit {} bytes",
            size, limits.max_output_bytes
        ).into());
    }
    Ok(())
}

/// Gets timeout duration for a module
pub fn get_timeout(limits: &ModuleLimits) -> Duration {
    Duration::from_millis(limits.timeout_ms)
}

/// Monitors execution time and kills if exceeded
pub fn check_timeout(elapsed_ms: u64, limits: &ModuleLimits) -> Result<(), Box<dyn Error>> {
    if elapsed_ms > limits.timeout_ms {
        return Err(format!(
            "TIMEOUT: Execution time {} ms exceeds limit {} ms",
            elapsed_ms, limits.timeout_ms
        ).into());
    }
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    
    #[test]
    fn test_check_input_size_ok() {
        let limits = ModuleLimits {
            timeout_ms: 30000,
            max_memory_mb: 512,
            max_cpu_percent: 80,
            max_output_bytes: 1024,
            max_input_bytes: 1024,
            allowed_file_paths: None,
            readonly_paths: None,
        };
        
        let input = "test";
        assert!(check_input_size(input, &limits).is_ok());
    }
    
    #[test]
    fn test_check_input_size_exceeded() {
        let limits = ModuleLimits {
            timeout_ms: 30000,
            max_memory_mb: 512,
            max_cpu_percent: 80,
            max_output_bytes: 1024,
            max_input_bytes: 10,
            allowed_file_paths: None,
            readonly_paths: None,
        };
        
        let input = "this is a long input that exceeds the limit";
        assert!(check_input_size(input, &limits).is_err());
    }
    
    #[test]
    fn test_check_timeout() {
        let limits = ModuleLimits {
            timeout_ms: 1000,
            max_memory_mb: 512,
            max_cpu_percent: 80,
            max_output_bytes: 1024,
            max_input_bytes: 1024,
            allowed_file_paths: None,
            readonly_paths: None,
        };
        
        assert!(check_timeout(500, &limits).is_ok());
        assert!(check_timeout(1500, &limits).is_err());
    }
}
