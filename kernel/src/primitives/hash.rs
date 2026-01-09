// Hashing Primitives
// SHA-256 hashing following shared/canonicalization/hashing.yaml rules

use sha2::{Sha256, Digest};

/// Hash bytes using SHA-256 and return lowercase hex string
/// This is the ONLY allowed hash function per hashing.yaml
pub fn hash_bytes(data: &[u8]) -> String {
    let mut hasher = Sha256::new();
    hasher.update(data);
    let result = hasher.finalize();
    
    // Convert to lowercase hex
    hex::encode(result)
}

/// Hash a string (UTF-8 encoded) using SHA-256
pub fn hash_string(data: &str) -> String {
    hash_bytes(data.as_bytes())
}

/// Hash canonical JSON (caller must ensure JSON is already canonical)
/// Per hashing.yaml: JSON MUST be canonicalized before hashing
pub fn hash_canonical_json(canonical_json: &str) -> String {
    hash_string(canonical_json)
}

/// Hash canonical YAML (caller must ensure YAML is already canonical)
/// Per hashing.yaml: YAML MUST be canonicalized before hashing
pub fn hash_canonical_yaml(canonical_yaml: &str) -> String {
    hash_string(canonical_yaml)
}

/// Verify that a hash matches expected value
/// Constant-time comparison to prevent timing attacks
pub fn verify_hash(data: &[u8], expected_hash: &str) -> bool {
    let actual_hash = hash_bytes(data);
    constant_time_compare(&actual_hash, expected_hash)
}

/// Constant-time string comparison
fn constant_time_compare(a: &str, b: &str) -> bool {
    if a.len() != b.len() {
        return false;
    }
    
    let mut result = 0u8;
    for (byte_a, byte_b) in a.bytes().zip(b.bytes()) {
        result |= byte_a ^ byte_b;
    }
    
    result == 0
}

#[cfg(test)]
mod tests {
    use super::*;

    #[test]
    fn test_hash_empty_string() {
        // SHA-256 of empty string (from test vectors)
        let hash = hash_string("");
        assert_eq!(
            hash,
            "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855"
        );
    }

    #[test]
    fn test_hash_hello_world() {
        // SHA-256 of "hello world" (from test vectors)
        let hash = hash_string("hello world");
        assert_eq!(
            hash,
            "b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9"
        );
    }

    #[test]
    fn test_hash_deterministic() {
        let data = "test data";
        let hash1 = hash_string(data);
        let hash2 = hash_string(data);
        assert_eq!(hash1, hash2);
    }

    #[test]
    fn test_hash_lowercase() {
        let hash = hash_string("test");
        // Ensure all hex chars are lowercase
        assert!(hash.chars().all(|c| c.is_ascii_hexdigit() && !c.is_ascii_uppercase()));
    }

    #[test]
    fn test_hash_length() {
        let hash = hash_string("test");
        // SHA-256 produces 64 hex characters (256 bits / 4 bits per hex char)
        assert_eq!(hash.len(), 64);
    }

    #[test]
    fn test_verify_hash_valid() {
        let data = b"hello world";
        let expected = "b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9";
        assert!(verify_hash(data, expected));
    }

    #[test]
    fn test_verify_hash_invalid() {
        let data = b"hello world";
        let wrong_hash = "0000000000000000000000000000000000000000000000000000000000000000";
        assert!(!verify_hash(data, wrong_hash));
    }

    #[test]
    fn test_constant_time_compare() {
        assert!(constant_time_compare("abc", "abc"));
        assert!(!constant_time_compare("abc", "abd"));
        assert!(!constant_time_compare("abc", "ab"));
    }
}
