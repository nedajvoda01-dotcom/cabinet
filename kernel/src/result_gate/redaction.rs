// Redaction
// Applies result profiles to filter fields

use serde::Deserialize;
use serde_json::{Value, Map};
use std::collections::{HashMap, HashSet};
use std::error::Error;
use std::fs;

use super::size_limits::SizeLimits;

#[derive(Debug, Clone, Deserialize)]
pub struct ResultProfile {
    pub name: String,
    pub description: String,
    pub max_response_size_bytes: u64,
    pub max_array_length: usize,
    pub max_string_length: usize,
    pub truncate_on_overflow: bool,
    pub allowed_fields: HashMap<String, Vec<String>>,
}

#[derive(Debug, Deserialize)]
pub struct ResultProfilesPolicy {
    pub profiles: HashMap<String, ResultProfile>,
    pub ui_profiles: HashMap<String, String>,
    pub redaction: Option<RedactionConfig>,
}

#[derive(Debug, Clone, Deserialize)]
struct RedactionConfig {
    sensitive_fields: Vec<String>,
    redacted_marker: String,
    hash_ids_for_public: bool,
}

/// Loads result profiles from policy
pub fn load_result_profiles() -> Result<ResultProfilesPolicy, Box<dyn Error>> {
    let policy_path = "/home/runner/work/cabinet/cabinet/system/policy/result_profiles.yaml";
    let content = fs::read_to_string(policy_path)
        .map_err(|e| format!("Failed to read result profiles policy: {}", e))?;
    
    let policy: ResultProfilesPolicy = serde_yaml::from_str(&content)
        .map_err(|e| format!("Failed to parse result profiles policy: {}", e))?;
    
    Ok(policy)
}

/// Gets profile for a UI
pub fn get_profile_for_ui<'a>(ui_id: &str, policy: &'a ResultProfilesPolicy) -> Result<&'a ResultProfile, Box<dyn Error>> {
    let profile_id = policy.ui_profiles.get(ui_id)
        .ok_or_else(|| format!("No profile mapping for UI: {}", ui_id))?;
    
    policy.profiles.get(profile_id)
        .ok_or_else(|| format!("Profile '{}' not found", profile_id).into())
}

/// Applies profile to redact fields from result
pub fn apply_profile(result: &Value, profile: &ResultProfile) -> Result<Value, Box<dyn Error>> {
    let mut redacted = result.clone();
    
    // Redact data field
    if let Some(data) = redacted.get_mut("data") {
        *data = redact_data(data, profile)?;
    }
    
    Ok(redacted)
}

fn redact_data(data: &Value, profile: &ResultProfile) -> Result<Value, Box<dyn Error>> {
    match data {
        Value::Object(obj) => {
            // Try to detect entity type from fields
            let entity_type = detect_entity_type(obj);
            
            if let Some(et) = entity_type {
                // Apply field filtering for this entity type
                if let Some(allowed_fields) = profile.allowed_fields.get(&et) {
                    let allowed_set: HashSet<_> = allowed_fields.iter().map(|s| s.as_str()).collect();
                    let mut filtered = Map::new();
                    
                    for (key, value) in obj {
                        if allowed_set.contains(key.as_str()) {
                            filtered.insert(key.clone(), value.clone());
                        }
                    }
                    
                    return Ok(Value::Object(filtered));
                }
            }
            
            // No specific entity type or profile, return as-is
            Ok(Value::Object(obj.clone()))
        }
        Value::Array(arr) => {
            // Redact each item in array
            let redacted_items: Result<Vec<_>, _> = arr.iter()
                .map(|item| redact_data(item, profile))
                .collect();
            Ok(Value::Array(redacted_items?))
        }
        _ => Ok(data.clone())
    }
}

/// Detects entity type from object fields
fn detect_entity_type(obj: &Map<String, Value>) -> Option<String> {
    // Heuristic: detect based on key fields
    if obj.contains_key("brand") && obj.contains_key("model") {
        return Some("listing".to_string());
    }
    if obj.contains_key("import_id") {
        return Some("import".to_string());
    }
    if obj.contains_key("email") && obj.contains_key("role") {
        return Some("user".to_string());
    }
    None
}

/// Gets size limits from profile
pub fn get_size_limits(profile: &ResultProfile) -> SizeLimits {
    SizeLimits {
        max_response_size_bytes: profile.max_response_size_bytes,
        max_array_length: profile.max_array_length,
        max_string_length: profile.max_string_length,
        truncate_on_overflow: profile.truncate_on_overflow,
    }
}

#[cfg(test)]
mod tests {
    use super::*;
    use serde_json::json;
    
    #[test]
    fn test_apply_profile_listing() {
        let mut allowed_fields = HashMap::new();
        allowed_fields.insert("listing".to_string(), vec![
            "id".to_string(),
            "brand".to_string(),
            "model".to_string(),
            "price".to_string(),
        ]);
        
        let profile = ResultProfile {
            name: "Public".to_string(),
            description: "Public profile".to_string(),
            max_response_size_bytes: 1000000,
            max_array_length: 100,
            max_string_length: 10000,
            truncate_on_overflow: false,
            allowed_fields,
        };
        
        let result = json!({
            "status": "success",
            "data": {
                "id": "123",
                "brand": "Toyota",
                "model": "Camry",
                "price": 25000,
                "owner_email": "secret@example.com",
                "internal_notes": "Secret notes"
            }
        });
        
        let redacted = apply_profile(&result, &profile).unwrap();
        
        // Should keep allowed fields
        assert!(redacted["data"]["id"].is_string());
        assert!(redacted["data"]["brand"].is_string());
        
        // Should remove non-allowed fields (they won't exist in the redacted object)
        assert!(redacted["data"].get("owner_email").is_none());
        assert!(redacted["data"].get("internal_notes").is_none());
    }
    
    #[test]
    fn test_detect_entity_type() {
        let mut obj = Map::new();
        obj.insert("id".to_string(), json!("123"));
        obj.insert("brand".to_string(), json!("Toyota"));
        obj.insert("model".to_string(), json!("Camry"));
        
        assert_eq!(detect_entity_type(&obj), Some("listing".to_string()));
    }
}
