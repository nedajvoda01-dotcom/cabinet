//! Registry Builder
//! Builds "read-model" registry for convenient consumption (not SSOT)
//!
//! Inputs (read-only):
//!   - system/canonical/* (primarily desired)
//!   - system/policy/* (if needed)
//!
//! Outputs (write):
//!   - system/registry/**
//!   - dist/reports/registry_report.json
//!
//! Prohibited:
//!   - Becoming source of truth (discrepancies resolved via intent→desired, not registry)
//!   - Reading/analyzing extensions/** as "system fact"

use std::collections::BTreeMap;
use std::fs;
use std::path::Path;
use std::process;
use serde_json::{json, Value};
use walkdir::WalkDir;

fn main() {
    println!("📚 Registry Builder - Building read-model registry\n");
    
    let mut errors = Vec::new();
    let mut warnings = Vec::new();
    let mut files_processed = 0;
    
    // Read canonical desired state
    println!("Reading system/canonical/desired/**...");
    let desired_dir = Path::new("system/canonical/desired");
    let desired_files = read_yaml_files(desired_dir);
    println!("  Found {} desired state files", desired_files.len());
    
    // Read policy files if they exist
    println!("\nReading system/policy/**...");
    let policy_dir = Path::new("system/policy");
    let policy_files = if policy_dir.exists() {
        read_yaml_files(policy_dir)
    } else {
        println!("  ℹ️  No policy directory");
        BTreeMap::new()
    };
    println!("  Found {} policy files", policy_files.len());
    
    // Build registry
    println!("\nBuilding registry read-model...");
    let registry_dir = Path::new("system/registry");
    fs::create_dir_all(registry_dir).unwrap_or_else(|e| {
        eprintln!("❌ Failed to create registry directory: {}", e);
        process::exit(1);
    });
    
    // Process each desired file into registry
    for (filename, value) in &desired_files {
        let registry_file = registry_dir.join(filename);
        match serde_yaml::to_string(value) {
            Ok(yaml) => {
                if let Err(e) = fs::write(&registry_file, yaml) {
                    errors.push(format!("Failed to write {}: {}", filename, e));
                } else {
                    files_processed += 1;
                    println!("  ✓ {}", filename);
                }
            }
            Err(e) => {
                errors.push(format!("Failed to serialize {}: {}", filename, e));
            }
        }
    }
    
    // Add metadata file
    let metadata = json!({
        "version": "1.0.0",
        "source": "system/canonical/desired",
        "built_at": std::time::SystemTime::now()
            .duration_since(std::time::UNIX_EPOCH)
            .unwrap()
            .as_secs(),
        "note": "This is a READ-MODEL only. Source of truth is system/intent → system/canonical/desired"
    });
    
    let metadata_file = registry_dir.join("_metadata.json");
    if let Err(e) = fs::write(&metadata_file, serde_json::to_string_pretty(&metadata).unwrap()) {
        warnings.push(format!("Failed to write metadata: {}", e));
    } else {
        println!("  ✓ _metadata.json");
    }
    
    // Generate report
    let report = generate_report(files_processed, &errors, &warnings);
    
    // Write report
    let report_path = "dist/reports/registry_report.json";
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
        println!("✅ Registry built successfully!");
        println!("📂 Output: system/registry/");
        println!("⚠️  Remember: Registry is READ-MODEL only, not source of truth!");
        process::exit(0);
    } else {
        println!("❌ Registry build failed with {} error(s)", errors.len());
        process::exit(1);
    }
}

fn read_yaml_files(dir: &Path) -> BTreeMap<String, Value> {
    let mut files = BTreeMap::new();
    
    if !dir.exists() {
        return files;
    }
    
    for entry in WalkDir::new(dir)
        .follow_links(false)
        .into_iter()
        .filter_map(|e| e.ok())
    {
        let path = entry.path();
        if path.is_file() && is_yaml(path) {
            if let Some(filename) = path.file_name().and_then(|n| n.to_str()) {
                if let Ok(content) = fs::read_to_string(path) {
                    if let Ok(value) = serde_yaml::from_str::<Value>(&content) {
                        files.insert(filename.to_string(), value);
                    }
                }
            }
        }
    }
    
    files
}

fn is_yaml(path: &Path) -> bool {
    path.extension()
        .and_then(|s| s.to_str())
        .map(|s| s == "yaml" || s == "yml")
        .unwrap_or(false)
}

fn generate_report(files_processed: usize, errors: &[String], warnings: &[String]) -> Value {
    let now = std::time::SystemTime::now()
        .duration_since(std::time::UNIX_EPOCH)
        .unwrap()
        .as_secs();
    
    json!({
        "version": "1.0.0",
        "timestamp": format!("{}", now),
        "tool": "registry_builder",
        "status": if errors.is_empty() { "SUCCESS" } else { "FAILED" },
        "deterministic": true,
        "inputs": {
            "canonical": "system/canonical/*",
            "policy": "system/policy/*"
        },
        "outputs": {
            "registry": "system/registry/**",
            "report": "dist/reports/registry_report.json"
        },
        "important_note": "Registry is READ-MODEL only. Source of truth is intent→desired.",
        "prohibited_actions": {
            "become_ssot": "MUST NOT be source of truth",
            "use_extensions": "MUST NOT read/analyze extensions/** as system fact"
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
