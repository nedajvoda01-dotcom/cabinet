//! Desired Builder
//! Builds runtime truth "desired" state from system/intent/**
//!
//! Inputs (read-only):
//!   - system/intent/**
//!   - shared/schemas/intent/**
//!   - shared/canonicalization/** (if rules exist)
//!
//! Outputs (write):
//!   - system/canonical/desired/*.yaml
//!   - dist/reports/desired_build_report.json
//!
//! Prohibited:
//!   - Writing to system/intent/**
//!   - Using extensions/** as source of truth
//!   - "Smart magic" - only deterministic desired state compilation

use std::collections::BTreeMap;
use std::fs;
use std::path::Path;
use std::process;
use serde_json::{json, Value};
use walkdir::WalkDir;

fn main() {
    println!("🏗️  Desired Builder - Compiling desired state from intent\n");
    
    let mut errors = Vec::new();
    let mut warnings = Vec::new();
    let mut files_processed = 0;
    
    // Read intent files
    println!("Reading system/intent/**...");
    let intent_dir = Path::new("system/intent");
    if !intent_dir.exists() {
        eprintln!("❌ system/intent/ directory does not exist");
        process::exit(1);
    }
    
    let mut intent_files = Vec::new();
    for entry in WalkDir::new(intent_dir)
        .follow_links(false)
        .into_iter()
        .filter_map(|e| e.ok())
    {
        let path = entry.path();
        if path.is_file() && is_yaml(path) {
            intent_files.push(path.to_path_buf());
        }
    }
    
    println!("  Found {} intent files", intent_files.len());
    
    // Process each intent file
    let output_dir = Path::new("system/canonical/desired");
    fs::create_dir_all(output_dir).unwrap_or_else(|e| {
        eprintln!("❌ Failed to create output directory: {}", e);
        process::exit(1);
    });
    
    for intent_file in &intent_files {
        match process_intent_file(intent_file, output_dir) {
            Ok(_) => {
                files_processed += 1;
                println!("  ✓ {}", intent_file.display());
            }
            Err(e) => {
                errors.push(format!("{}: {}", intent_file.display(), e));
                eprintln!("  ✗ {}: {}", intent_file.display(), e);
            }
        }
    }
    
    // Generate report
    let report = generate_report(files_processed, &errors, &warnings);
    
    // Write report
    let report_path = "dist/reports/desired_build_report.json";
    fs::create_dir_all("dist/reports").unwrap_or_else(|e| {
        eprintln!("❌ Failed to create reports directory: {}", e);
        process::exit(1);
    });
    
    fs::write(report_path, serde_json::to_string_pretty(&report).unwrap())
        .unwrap_or_else(|e| {
            eprintln!("❌ Failed to write report: {}", e);
            process::exit(1);
        });
    
    println!("\n📄 Report written to: {}", report_path);
    
    if errors.is_empty() {
        println!("✅ Desired state built successfully!");
        println!("📂 Output: system/canonical/desired/");
        process::exit(0);
    } else {
        println!("❌ Build failed with {} error(s)", errors.len());
        process::exit(1);
    }
}

fn is_yaml(path: &Path) -> bool {
    path.extension()
        .and_then(|s| s.to_str())
        .map(|s| s == "yaml" || s == "yml")
        .unwrap_or(false)
}

fn process_intent_file(intent_file: &Path, output_dir: &Path) -> Result<(), String> {
    // Read intent file
    let content = fs::read_to_string(intent_file)
        .map_err(|e| format!("Failed to read: {}", e))?;
    
    // Parse YAML
    let value: Value = serde_yaml::from_str(&content)
        .map_err(|e| format!("Failed to parse YAML: {}", e))?;
    
    // Canonicalize (deterministic ordering)
    let canonical_value = canonicalize_value(value);
    
    // Generate output filename
    let filename = intent_file.file_name()
        .ok_or("Invalid filename")?;
    let output_file = output_dir.join(filename);
    
    // Write canonical YAML
    let output_content = serde_yaml::to_string(&canonical_value)
        .map_err(|e| format!("Failed to serialize: {}", e))?;
    
    fs::write(&output_file, output_content)
        .map_err(|e| format!("Failed to write: {}", e))?;
    
    Ok(())
}

fn canonicalize_value(value: Value) -> Value {
    match value {
        Value::Object(map) => {
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

fn generate_report(files_processed: usize, errors: &[String], warnings: &[String]) -> Value {
    let now = std::time::SystemTime::now()
        .duration_since(std::time::UNIX_EPOCH)
        .unwrap()
        .as_secs();
    
    json!({
        "version": "1.0.0",
        "timestamp": format!("{}", now),
        "tool": "desired_builder",
        "status": if errors.is_empty() { "SUCCESS" } else { "FAILED" },
        "deterministic": true,
        "inputs": {
            "intent": "system/intent/**",
            "schemas": "shared/schemas/intent/**"
        },
        "outputs": {
            "desired_state": "system/canonical/desired/*.yaml",
            "report": "dist/reports/desired_build_report.json"
        },
        "prohibited_actions": {
            "write_intent": "MUST NOT write to system/intent/**",
            "use_extensions": "MUST NOT use extensions/** as source of truth"
        },
        "summary": {
            "files_processed": files_processed,
            "errors": errors.len(),
            "warnings": warnings.len()
        },
        "errors": errors,
        "warnings": warnings
    })
}
