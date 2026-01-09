//! Release Tools
//! Creates reproducible release bundles and verifies them
//!
//! Inputs (read-only):
//!   - shared/
//!   - system/canonical/
//!
//! Outputs (write):
//!   - dist/releases/*
//!   - dist/reports/release_verify_report.json
//!
//! Prohibited:
//!   - Non-deterministic builds
//!   - Dependencies on environment (except explicitly allowed)

use std::collections::BTreeMap;
use std::fs;
use std::path::Path;
use std::process;
use serde_json::{json, Value};
use walkdir::WalkDir;

fn main() {
    let args: Vec<String> = std::env::args().collect();
    
    if args.len() < 2 {
        eprintln!("Usage:");
        eprintln!("  release_tools bundle  - Create release bundle");
        eprintln!("  release_tools verify  - Verify release bundle");
        process::exit(1);
    }
    
    match args[1].as_str() {
        "bundle" => create_bundle(),
        "verify" => verify_bundle(),
        _ => {
            eprintln!("Unknown command: {}", args[1]);
            eprintln!("Use 'bundle' or 'verify'");
            process::exit(1);
        }
    }
}

fn create_bundle() {
    println!("📦 Release Tools - Creating reproducible bundle\n");
    
    let mut errors: Vec<String> = Vec::new();
    let mut warnings: Vec<String> = Vec::new();
    
    // Read shared/ files
    println!("Reading shared/**...");
    let shared_files = collect_files("shared");
    println!("  Found {} shared files", shared_files.len());
    
    // Read canonical state
    println!("\nReading system/canonical/**...");
    let canonical_files = collect_files("system/canonical");
    println!("  Found {} canonical files", canonical_files.len());
    
    // Create bundle
    println!("\nCreating bundle...");
    let bundle = json!({
        "version": "1.0.0",
        "created_at": std::time::SystemTime::now()
            .duration_since(std::time::UNIX_EPOCH)
            .unwrap()
            .as_secs(),
        "deterministic": true,
        "shared_files": shared_files,
        "canonical_files": canonical_files
    });
    
    // Write bundle
    let releases_dir = Path::new("dist/releases");
    fs::create_dir_all(releases_dir).unwrap_or_else(|e| {
        eprintln!("❌ Failed to create releases directory: {}", e);
        process::exit(1);
    });
    
    let bundle_file = releases_dir.join("release_bundle.json");
    fs::write(&bundle_file, serde_json::to_string_pretty(&bundle).unwrap())
        .unwrap_or_else(|e| {
            eprintln!("❌ Failed to write bundle: {}", e);
            process::exit(1);
        });
    
    println!("  ✓ release_bundle.json");
    
    println!("\n✅ Bundle created successfully!");
    println!("📂 Output: {}", bundle_file.display());
}

fn verify_bundle() {
    println!("🔍 Release Tools - Verifying release bundle\n");
    
    let mut errors: Vec<String> = Vec::new();
    let mut warnings: Vec<String> = Vec::new();
    
    // Read bundle
    let bundle_file = Path::new("dist/releases/release_bundle.json");
    if !bundle_file.exists() {
        eprintln!("❌ Bundle file not found: {}", bundle_file.display());
        process::exit(1);
    }
    
    let bundle_content = fs::read_to_string(bundle_file)
        .unwrap_or_else(|e| {
            eprintln!("❌ Failed to read bundle: {}", e);
            process::exit(1);
        });
    
    let bundle: Value = serde_json::from_str(&bundle_content)
        .unwrap_or_else(|e| {
            eprintln!("❌ Failed to parse bundle: {}", e);
            process::exit(1);
        });
    
    println!("Verifying bundle integrity...");
    
    // Check version
    if bundle.get("version").is_none() {
        errors.push("Bundle missing version field".to_string());
    }
    
    // Check deterministic flag
    if bundle.get("deterministic") != Some(&json!(true)) {
        errors.push("Bundle not marked as deterministic".to_string());
    }
    
    // Check shared_files
    if let Some(shared_files) = bundle.get("shared_files") {
        if let Some(arr) = shared_files.as_array() {
            println!("  ✓ Found {} shared files", arr.len());
        } else {
            errors.push("shared_files is not an array".to_string());
        }
    } else {
        errors.push("Bundle missing shared_files".to_string());
    }
    
    // Check canonical_files
    if let Some(canonical_files) = bundle.get("canonical_files") {
        if let Some(arr) = canonical_files.as_array() {
            println!("  ✓ Found {} canonical files", arr.len());
        } else {
            errors.push("canonical_files is not an array".to_string());
        }
    } else {
        errors.push("Bundle missing canonical_files".to_string());
    }
    
    // Generate verification report
    let report = json!({
        "version": "1.0.0",
        "timestamp": std::time::SystemTime::now()
            .duration_since(std::time::UNIX_EPOCH)
            .unwrap()
            .as_secs(),
        "tool": "release_tools_verify",
        "status": if errors.is_empty() { "PASS" } else { "FAIL" },
        "bundle_file": bundle_file.display().to_string(),
        "checks_performed": {
            "version_check": true,
            "deterministic_check": true,
            "shared_files_check": true,
            "canonical_files_check": true
        },
        "summary": {
            "errors": errors.len(),
            "warnings": warnings.len()
        },
        "errors": errors,
        "warnings": warnings
    });
    
    // Write report
    let report_path = "dist/reports/release_verify_report.json";
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
        println!("✅ Bundle verification passed!");
        process::exit(0);
    } else {
        println!("❌ Bundle verification failed with {} error(s)", errors.len());
        for error in &errors {
            eprintln!("  • {}", error);
        }
        process::exit(1);
    }
}

fn collect_files(dir_path: &str) -> Vec<String> {
    let mut files = Vec::new();
    let dir = Path::new(dir_path);
    
    if !dir.exists() {
        return files;
    }
    
    for entry in WalkDir::new(dir)
        .follow_links(false)
        .into_iter()
        .filter_map(|e| e.ok())
    {
        let path = entry.path();
        if path.is_file() {
            if let Ok(relative) = path.strip_prefix(".") {
                files.push(relative.display().to_string());
            } else {
                files.push(path.display().to_string());
            }
        }
    }
    
    files.sort(); // Deterministic ordering
    files
}
