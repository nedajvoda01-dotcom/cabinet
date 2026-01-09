// ID Generation Primitives
// Deterministic and random ID generation following shared/canonicalization/id_generation.yaml

use uuid::Uuid;
use super::hash::hash_string;

/// Generate a random UUID v4 (non-deterministic)
/// Uses cryptographically secure random source
pub fn generate_uuid_v4() -> String {
    Uuid::new_v4().to_string()
}

/// Generate a deterministic ID from seed content
/// Uses SHA-256 hash → UUID format
/// Same seed always produces same ID
pub fn generate_deterministic_id(seed: &str) -> String {
    let hash = hash_string(seed);
    
    // Take first 32 hex chars (16 bytes) from hash
    let hex_part = &hash[0..32];
    
    // Format as UUID: xxxxxxxx-xxxx-4xxx-yxxx-xxxxxxxxxxxx
    // Set version (4) and variant (RFC4122) bits
    format!(
        "{}-{}-4{}-{}{}-{}",
        &hex_part[0..8],
        &hex_part[8..12],
        &hex_part[13..16],  // Skip one char for version bit
        "8",  // Variant bits (10xx in binary = 8-b in hex, use 8 for simplicity)
        &hex_part[17..20],
        &hex_part[20..32]
    )
}

/// Generate a prefixed random ID
/// Format: {prefix}{uuid-v4}
pub fn generate_random_id(prefix: &str) -> String {
    format!("{}{}", prefix, generate_uuid_v4())
}

/// Generate a prefixed deterministic ID from seed
/// Format: {prefix}{deterministic-uuid}
pub fn generate_content_based_id(prefix: &str, seed: &str) -> String {
    format!("{}{}", prefix, generate_deterministic_id(seed))
}

/// ID Types with prefixes (from id_generation.yaml)
pub mod prefixes {
    pub const MESSAGE: &str = "msg-";
    pub const ENVELOPE: &str = "env-";
    pub const IMPORT: &str = "import-";
    pub const LISTING: &str = "listing-";
    pub const USER: &str = "user-";
    pub const SESSION: &str = "session-";
    pub const TRACE: &str = "trace-";
    pub const SPAN: &str = "span-";
    pub const WORKFLOW: &str = "workflow-";
    pub const JOB: &str = "job-";
}

/// Validate ID format
/// Checks: prefix + UUID format, lowercase only
pub fn validate_id_format(id: &str) -> Result<(), String> {
    // Find the UUID part (after last hyphen in prefix)
    let parts: Vec<&str> = id.splitn(2, |c: char| c == '-' && id.len() - c.len_utf8() > 36).collect();
    
    if parts.len() != 2 {
        return Err("ID must have format: prefix-uuid".to_string());
    }
    
    let prefix = parts[0];
    let uuid_part = parts[1];
    
    // Check prefix is lowercase alphanumeric with hyphens
    if !prefix.chars().all(|c| c.is_ascii_lowercase() || c.is_ascii_digit() || c == '-') {
        return Err("Prefix must be lowercase alphanumeric with hyphens".to_string());
    }
    
    // Check UUID part has correct length
    if uuid_part.len() != 36 {
        return Err("UUID part must be 36 characters".to_string());
    }
    
    // Check UUID format (rough validation)
    let uuid_chars: Vec<char> = uuid_part.chars().collect();
    if uuid_chars[8] != '-' || uuid_chars[13] != '-' || uuid_chars[18] != '-' || uuid_chars[23] != '-' {
        return Err("Invalid UUID format".to_string());
    }
    
    // Check all lowercase
    if id.chars().any(|c| c.is_ascii_uppercase()) {
        return Err("ID must be lowercase only".to_string());
    }
    
    Ok(())
}

/// Entity-specific ID generators
pub mod entities {
    use super::*;

    /// Generate message ID (random)
    pub fn message_id() -> String {
        generate_random_id(prefixes::MESSAGE)
    }

    /// Generate import ID (deterministic from CSV hash)
    pub fn import_id(csv_content_hash: &str) -> String {
        generate_content_based_id(prefixes::IMPORT, csv_content_hash)
    }

    /// Generate listing ID (deterministic from external_id)
    pub fn listing_id(external_id: &str) -> String {
        generate_content_based_id(prefixes::LISTING, external_id)
    }

    /// Generate session ID (random)
    pub fn session_id() -> String {
        generate_random_id(prefixes::SESSION)
    }

    /// Generate trace ID (random)
    pub fn trace_id() -> String {
        generate_random_id(prefixes::TRACE)
    }
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_generate_uuid_v4_format() {
        let uuid = generate_uuid_v4();
        assert_eq!(uuid.len(), 36);
        assert!(uuid.contains('-'));
    }

    #[test]
    fn test_generate_uuid_v4_unique() {
        let uuid1 = generate_uuid_v4();
        let uuid2 = generate_uuid_v4();
        assert_ne!(uuid1, uuid2);
    }

    #[test]
    fn test_generate_deterministic_id() {
        let seed = "test-seed";
        let id1 = generate_deterministic_id(seed);
        let id2 = generate_deterministic_id(seed);
        
        // Same seed = same ID
        assert_eq!(id1, id2);
        assert_eq!(id1.len(), 36);
    }

    #[test]
    fn test_generate_deterministic_id_different_seeds() {
        let id1 = generate_deterministic_id("seed1");
        let id2 = generate_deterministic_id("seed2");
        
        // Different seeds = different IDs
        assert_ne!(id1, id2);
    }

    #[test]
    fn test_generate_random_id() {
        let id = generate_random_id(prefixes::MESSAGE);
        assert!(id.starts_with(prefixes::MESSAGE));
        assert_eq!(id.len(), prefixes::MESSAGE.len() + 36);
    }

    #[test]
    fn test_generate_content_based_id() {
        let seed = "CAR-2026-001";
        let id1 = generate_content_based_id(prefixes::LISTING, seed);
        let id2 = generate_content_based_id(prefixes::LISTING, seed);
        
        // Same seed = same ID
        assert_eq!(id1, id2);
        assert!(id1.starts_with(prefixes::LISTING));
    }

    #[test]
    fn test_validate_id_format_valid() {
        let id = entities::message_id();
        assert!(validate_id_format(&id).is_ok());
    }

    #[test]
    fn test_validate_id_format_uppercase() {
        let id = "MSG-550E8400-E29B-41D4-A716-446655440000";
        assert!(validate_id_format(id).is_err());
    }

    #[test]
    fn test_validate_id_format_no_prefix() {
        let id = "550e8400-e29b-41d4-a716-446655440000";
        assert!(validate_id_format(id).is_err());
    }

    #[test]
    fn test_entity_message_id() {
        let id = entities::message_id();
        assert!(id.starts_with(prefixes::MESSAGE));
        assert!(validate_id_format(&id).is_ok());
    }

    #[test]
    fn test_entity_listing_id_deterministic() {
        let external_id = "CAR-2026-001";
        let id1 = entities::listing_id(external_id);
        let id2 = entities::listing_id(external_id);
        
        assert_eq!(id1, id2);
        assert!(id1.starts_with(prefixes::LISTING));
    }

    #[test]
    fn test_entity_import_id_deterministic() {
        let csv_hash = "f8c3d54e8c6e4d5f6e7e8d9e0f1e2e3e";
        let id1 = entities::import_id(csv_hash);
        let id2 = entities::import_id(csv_hash);
        
        assert_eq!(id1, id2);
        assert!(id1.starts_with(prefixes::IMPORT));
    }
}
