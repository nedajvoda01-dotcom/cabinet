// Size Limits
// Enforces size limits on results

use serde_json::Value;
use std::error::Error;

#[derive(Debug, Clone)]
pub struct SizeLimits {
    pub max_response_size_bytes: u64,
    pub max_array_length: usize,
    pub max_string_length: usize,
    pub truncate_on_overflow: bool,
}

/// Validates result size against limits
pub fn check_size_limits(result: &Value, limits: &SizeLimits) -> Result<(), Box<dyn Error>> {
    // Check total serialized size
    let serialized = serde_json::to_string(result)?;
    let size = serialized.len() as u64;
    
    if size > limits.max_response_size_bytes {
        if limits.truncate_on_overflow {
            return Err(format!(
                "RESULT_TOO_LARGE: Result size {} bytes exceeds limit {} bytes (truncation not implemented)",
                size, limits.max_response_size_bytes
            ).into());
        } else {
            return Err(format!(
                "RESULT_TOO_LARGE: Result size {} bytes exceeds limit {} bytes",
                size, limits.max_response_size_bytes
            ).into());
        }
    }
    
    // Check array lengths and string lengths recursively
    check_value_limits(result, limits)?;
    
    Ok(())
}

fn check_value_limits(value: &Value, limits: &SizeLimits) -> Result<(), Box<dyn Error>> {
    match value {
        Value::Object(obj) => {
            for (_key, val) in obj {
                check_value_limits(val, limits)?;
            }
        }
        Value::Array(arr) => {
            if arr.len() > limits.max_array_length {
                return Err(format!(
                    "RESULT_TOO_LARGE: Array length {} exceeds limit {}",
                    arr.len(), limits.max_array_length
                ).into());
            }
            for val in arr {
                check_value_limits(val, limits)?;
            }
        }
        Value::String(s) => {
            if s.len() > limits.max_string_length {
                return Err(format!(
                    "RESULT_TOO_LARGE: String length {} exceeds limit {}",
                    s.len(), limits.max_string_length
                ).into());
            }
        }
        _ => {}
    }
    
    Ok(())
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_size_within_limits() {
        let result = json!({
            "status": "success",
            "data": {
                "items": [1, 2, 3]
            }
        });
        
        let limits = SizeLimits {
            max_response_size_bytes: 1000,
            max_array_length: 10,
            max_string_length: 100,
            truncate_on_overflow: false,
        };
        
        assert!(check_size_limits(&result, &limits).is_ok());
    }
    
    #[test]
    fn test_array_too_long() {
        let items: Vec<i32> = (0..200).collect();
        let result = json!({
            "status": "success",
            "data": {
                "items": items
            }
        });
        
        let limits = SizeLimits {
            max_response_size_bytes: 100000,
            max_array_length: 100,
            max_string_length: 1000,
            truncate_on_overflow: false,
        };
        
        assert!(check_size_limits(&result, &limits).is_err());
    }
    
    #[test]
    fn test_string_too_long() {
        let long_string = "a".repeat(2000);
        let result = json!({
            "status": "success",
            "data": {
                "description": long_string
            }
        });
        
        let limits = SizeLimits {
            max_response_size_bytes: 100000,
            max_array_length: 100,
            max_string_length: 1000,
            truncate_on_overflow: false,
        };
        
        assert!(check_size_limits(&result, &limits).is_err());
    }
}
