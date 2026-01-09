// Hashing Primitives Tests
// Tests against shared/test_vectors/hashing/vectors.yaml

#[test]
fn test_hash_empty_string() {
    use kernel::primitives::hash::hash_string;
    
    // From test vector hash-001
    let hash = hash_string("");
    assert_eq!(
        hash,
        "e3b0c44298fc1c149afbf4c8996fb92427ae41e4649b934ca495991b7852b855",
        "SHA-256 of empty string"
    );
}

#[test]
fn test_hash_hello_world() {
    use kernel::primitives::hash::hash_string;
    
    // From test vector hash-002
    let hash = hash_string("hello world");
    assert_eq!(
        hash,
        "b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9",
        "SHA-256 of 'hello world'"
    );
}

#[test]
fn test_hash_deterministic() {
    use kernel::primitives::hash::hash_string;
    
    let data = "test data for determinism";
    let hash1 = hash_string(data);
    let hash2 = hash_string(data);
    
    assert_eq!(hash1, hash2, "Hashing must be deterministic");
}

#[test]
fn test_hash_output_format() {
    use kernel::primitives::hash::hash_string;
    
    let hash = hash_string("test");
    
    // Must be lowercase hex
    assert!(hash.chars().all(|c| c.is_ascii_hexdigit() && !c.is_ascii_uppercase()));
    
    // SHA-256 produces 64 hex characters
    assert_eq!(hash.len(), 64);
}

#[test]
fn test_verify_hash() {
    use kernel::primitives::hash::verify_hash;
    
    let data = b"hello world";
    let expected = "b94d27b9934d3e08a52e52d7da7dabfac484efe37a5380ee9088f7ace2efcde9";
    
    assert!(verify_hash(data, expected), "Hash verification should succeed");
}

#[test]
fn test_verify_hash_mismatch() {
    use kernel::primitives::hash::verify_hash;
    
    let data = b"hello world";
    let wrong_hash = "0000000000000000000000000000000000000000000000000000000000000000";
    
    assert!(!verify_hash(data, wrong_hash), "Hash verification should fail for wrong hash");
}

#[test]
fn test_hash_canonical_json() {
    use kernel::primitives::hash::hash_canonical_json;
    
    // Canonical JSON (already sorted)
    let canonical = r#"{"a":"first","z":"last"}"#;
    let hash = hash_canonical_json(canonical);
    
    // Should produce consistent hash
    assert_eq!(hash.len(), 64);
    assert!(hash.chars().all(|c| c.is_ascii_hexdigit()));
}
