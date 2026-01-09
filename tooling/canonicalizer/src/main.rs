//! Canonicalizer
//! Produces deterministic YAML/JSON output with consistent ordering and formatting
//!
//! Inputs (read):
//!   - Any YAML/JSON files specified by user (typically system/intent/**, system/policy/**, derived)
//!
//! Outputs (write):
//!   - dist/canonicalized/* (default mode)
//!   - In-place rewrite (when --rewrite flag is used)
//!
//! Prohibited:
//!   - Changing semantics (only formatting)
//!   - Non-deterministic output

use std::collections::BTreeMap;
use std::fs;
use std::path::{Path, PathBuf};
use std::process;
use serde_json::{json, Value};
use walkdir::WalkDir;

fn main() {
    println!("🔄 Canonicalizer - Deterministic YAML/JSON formatting\n");
    
    let args: Vec<String> = std::env::args().collect();
    
    let (input_paths, rewrite_mode) = parse_args(&args);
    
    if input_paths.is_empty() {
        eprintln!("Usage: canonicalizer [--rewrite] <path1> [path2...]");
        eprintln!("\nExamples:");
        eprintln!("  canonicalizer system/intent");
        eprintln!("  canonicalizer --rewrite system/intent/company.intent.yaml");
        eprintln!("  canonicalizer system/intent system/policy");
        process::exit(1);
    }
    
    let mut processed_files = 0;
    let mut errors = Vec::new();
    
    for input_path in &input_paths {
        let path = Path::new(input_path);
        
        if path.is_file() {
            match canonicalize_file(path, rewrite_mode) {
                Ok(_) => {
                    processed_files += 1;
                    println!("  ✓ {}", path.display());
                }
                Err(e) => {
                    errors.push(format!("{}: {}", path.display(), e));
                    eprintln!("  ✗ {}: {}", path.display(), e);
                }
            }
        } else if path.is_dir() {
            for entry in WalkDir::new(path)
                .follow_links(false)
                .into_iter()
                .filter_map(|e| e.ok())
            {
                let file_path = entry.path();
                if file_path.is_file() && is_yaml_or_json(file_path) {
                    match canonicalize_file(file_path, rewrite_mode) {
                        Ok(_) => {
                            processed_files += 1;
                            println!("  ✓ {}", file_path.display());
                        }
                        Err(e) => {
                            errors.push(format!("{}: {}", file_path.display(), e));
                            eprintln!("  ✗ {}: {}", file_path.display(), e);
                        }
                    }
                }
            }
        } else {
            eprintln!("  ⚠️  Path not found: {}", input_path);
        }
    }
    
    println!("\n📊 Summary:");
    println!("  Processed: {} files", processed_files);
    println!("  Errors: {}", errors.len());
    
    if !rewrite_mode {
        println!("  Output: dist/canonicalized/");
    } else {
        println!("  Mode: In-place rewrite");
    }
    
    if errors.is_empty() {
        println!("\n✅ Canonicalization complete!");
        process::exit(0);
    } else {
        println!("\n❌ Canonicalization failed with {} error(s)", errors.len());
        process::exit(1);
    }
}

fn parse_args(args: &[String]) -> (Vec<String>, bool) {
    let mut paths = Vec::new();
    let mut rewrite_mode = false;
    
    for arg in args.iter().skip(1) {
        if arg == "--rewrite" {
            rewrite_mode = true;
        } else {
            paths.push(arg.clone());
        }
    }
    
    (paths, rewrite_mode)
}

fn is_yaml_or_json(path: &Path) -> bool {
    path.extension()
        .and_then(|s| s.to_str())
        .map(|s| matches!(s, "yaml" | "yml" | "json"))
        .unwrap_or(false)
}

fn canonicalize_file(path: &Path, rewrite_mode: bool) -> Result<(), String> {
    let content = fs::read_to_string(path)
        .map_err(|e| format!("Failed to read: {}", e))?;
    
    let is_json = path.extension()
        .and_then(|s| s.to_str())
        .map(|s| s == "json")
        .unwrap_or(false);
    
    // Parse the content
    let value: Value = if is_json {
        serde_json::from_str(&content)
            .map_err(|e| format!("Failed to parse JSON: {}", e))?
    } else {
        serde_yaml::from_str(&content)
            .map_err(|e| format!("Failed to parse YAML: {}", e))?
    };
    
    // Canonicalize (sort keys recursively)
    let canonical_value = canonicalize_value(value);
    
    // Serialize in canonical form
    let output = if is_json {
        serde_json::to_string_pretty(&canonical_value)
            .map_err(|e| format!("Failed to serialize JSON: {}", e))?
    } else {
        serde_yaml::to_string(&canonical_value)
            .map_err(|e| format!("Failed to serialize YAML: {}", e))?
    };
    
    // Write output
    if rewrite_mode {
        fs::write(path, output)
            .map_err(|e| format!("Failed to write: {}", e))?;
    } else {
        // Write to dist/canonicalized/ preserving directory structure
        let output_path = get_output_path(path)?;
        if let Some(parent) = output_path.parent() {
            fs::create_dir_all(parent)
                .map_err(|e| format!("Failed to create output directory: {}", e))?;
        }
        fs::write(&output_path, output)
            .map_err(|e| format!("Failed to write: {}", e))?;
    }
    
    Ok(())
}

fn canonicalize_value(value: Value) -> Value {
    match value {
        Value::Object(map) => {
            // Sort keys using BTreeMap for deterministic ordering
            let mut sorted: BTreeMap<String, Value> = BTreeMap::new();
            for (k, v) in map {
                sorted.insert(k, canonicalize_value(v));
            }
            Value::Object(sorted.into_iter().collect())
        }
        Value::Array(arr) => {
            Value::Array(arr.into_iter().map(canonicalize_value).collect())
        }
        _ => value,
    }
}

fn get_output_path(input_path: &Path) -> Result<PathBuf, String> {
    let mut output_path = PathBuf::from("dist/canonicalized");
    
    // Preserve relative path structure
    if let Ok(relative) = input_path.strip_prefix(".") {
        output_path.push(relative);
    } else {
        output_path.push(input_path);
    }
    
    Ok(output_path)
}
