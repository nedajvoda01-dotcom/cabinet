//! Diff Builder
//! Compares desired vs observed state and builds diff (data-only, not commands)
//!
//! Inputs (read-only):
//!   - system/canonical/desired/*.yaml
//!   - system/canonical/observed/*.yaml
//!   - (or dist/reports/* if observed is written there)
//!
//! Outputs (write):
//!   - system/canonical/diff/*.yaml
//!   - dist/reports/diff_report.json
//!
//! Prohibited:
//!   - Modifying desired/observed
//!   - Generating executable actions/DSL (diff is declarative only)

use std::collections::{BTreeMap, HashSet};
use std::fs;
use std::path::Path;
use std::process;
use serde_json::{json, Value};
use walkdir::WalkDir;

fn main() {
    println!("🔍 Diff Builder - Comparing desired vs observed state\n");
    
    let mut errors = Vec::new();
    let mut warnings = Vec::new();
    
    // Read desired state
    println!("Reading system/canonical/desired/**...");
    let desired_dir = Path::new("system/canonical/desired");
    let desired_files = read_yaml_files(desired_dir);
    println!("  Found {} desired state files", desired_files.len());
    
    // Read observed state
    println!("\nReading system/canonical/observed/**...");
    let observed_dir = Path::new("system/canonical/observed");
    let observed_files = if observed_dir.exists() {
        read_yaml_files(observed_dir)
    } else {
        println!("  ℹ️  No observed state directory (creating empty baseline)");
        BTreeMap::new()
    };
    println!("  Found {} observed state files", observed_files.len());
    
    // Compute diff
    println!("\nComputing diff...");
    let diff_results = compute_diff(&desired_files, &observed_files);
    
    // Write diff files
    let diff_dir = Path::new("system/canonical/diff");
    fs::create_dir_all(diff_dir).unwrap_or_else(|e| {
        eprintln!("❌ Failed to create diff directory: {}", e);
        process::exit(1);
    });
    
    for (filename, diff_content) in &diff_results {
        let output_file = diff_dir.join(filename);
        match serde_yaml::to_string(diff_content) {
            Ok(yaml) => {
                if let Err(e) = fs::write(&output_file, yaml) {
                    errors.push(format!("Failed to write {}: {}", filename, e));
                } else {
                    println!("  ✓ {}", filename);
                }
            }
            Err(e) => {
                errors.push(format!("Failed to serialize {}: {}", filename, e));
            }
        }
    }
    
    // Generate summary
    let added_count = desired_files.keys()
        .filter(|k| !observed_files.contains_key(*k))
        .count();
    let removed_count = observed_files.keys()
        .filter(|k| !desired_files.contains_key(*k))
        .count();
    let modified_count = diff_results.len() - added_count;
    
    println!("\n📊 Diff Summary:");
    println!("  Added: {} files", added_count);
    println!("  Modified: {} files", modified_count);
    println!("  Removed: {} files", removed_count);
    
    // Generate report
    let report = generate_report(&diff_results, added_count, modified_count, removed_count, &errors, &warnings);
    
    // Write report
    let report_path = "dist/reports/diff_report.json";
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
        println!("✅ Diff built successfully!");
        println!("📂 Output: system/canonical/diff/");
        process::exit(0);
    } else {
        println!("❌ Diff build failed with {} error(s)", errors.len());
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

fn compute_diff(desired: &BTreeMap<String, Value>, observed: &BTreeMap<String, Value>) -> BTreeMap<String, Value> {
    let mut diffs = BTreeMap::new();
    
    // Find all files that need changes (added or modified)
    for (filename, desired_value) in desired {
        let diff_entry = if let Some(observed_value) = observed.get(filename) {
            // File exists - compare
            if desired_value != observed_value {
                json!({
                    "status": "modified",
                    "filename": filename,
                    "desired": desired_value,
                    "observed": observed_value
                })
            } else {
                continue; // No diff
            }
        } else {
            // File doesn't exist in observed
            json!({
                "status": "added",
                "filename": filename,
                "desired": desired_value
            })
        };
        
        diffs.insert(format!("diff_{}", filename), diff_entry);
    }
    
    // Find removed files
    for (filename, observed_value) in observed {
        if !desired.contains_key(filename) {
            let diff_entry = json!({
                "status": "removed",
                "filename": filename,
                "observed": observed_value
            });
            diffs.insert(format!("diff_{}", filename), diff_entry);
        }
    }
    
    diffs
}

fn generate_report(
    diffs: &BTreeMap<String, Value>,
    added: usize,
    modified: usize,
    removed: usize,
    errors: &[String],
    warnings: &[String],
) -> Value {
    let now = std::time::SystemTime::now()
        .duration_since(std::time::UNIX_EPOCH)
        .unwrap()
        .as_secs();
    
    json!({
        "version": "1.0.0",
        "timestamp": format!("{}", now),
        "tool": "diff_builder",
        "status": if errors.is_empty() { "SUCCESS" } else { "FAILED" },
        "deterministic": true,
        "inputs": {
            "desired": "system/canonical/desired/*.yaml",
            "observed": "system/canonical/observed/*.yaml"
        },
        "outputs": {
            "diff": "system/canonical/diff/*.yaml",
            "report": "dist/reports/diff_report.json"
        },
        "prohibited_actions": {
            "modify_inputs": "MUST NOT modify desired or observed",
            "generate_commands": "Diff is declarative only, not executable"
        },
        "summary": {
            "total_diffs": diffs.len(),
            "added": added,
            "modified": modified,
            "removed": removed,
            "errors": errors.len(),
            "warnings": warnings.len()
        },
        "errors": errors,
        "warnings": warnings
    })
}
