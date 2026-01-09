#!/bin/bash
# Theme 7 Tooling Pipeline - Full Test
# Runs all 6 tools in sequence to demonstrate the deterministic pipeline

set -e

echo "════════════════════════════════════════════════════════════════════════════"
echo "                    THEME 7 TOOLING PIPELINE TEST"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""

# Colors for output
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Change to repo root
cd "$(dirname "$0")/.."

echo -e "${BLUE}Step 1/6: System Validator${NC}"
echo "Validating system/ data against schemas and invariants..."
./tooling/system_validator/target/release/system_validator
echo ""

echo -e "${BLUE}Step 2/6: Canonicalizer${NC}"
echo "Canonicalizing intent files..."
./tooling/canonicalizer/target/release/canonicalizer system/intent
echo ""

echo -e "${BLUE}Step 3/6: Desired Builder${NC}"
echo "Building desired state from intent..."
./tooling/desired_builder/target/release/desired_builder
echo ""

echo -e "${BLUE}Step 4/6: Diff Builder${NC}"
echo "Computing diff between desired and observed..."
./tooling/diff_builder/target/release/diff_builder
echo ""

echo -e "${BLUE}Step 5/6: Registry Builder${NC}"
echo "Building read-model registry..."
./tooling/registry_builder/target/release/registry_builder
echo ""

echo -e "${BLUE}Step 6/6: Release Tools${NC}"
echo "Creating and verifying release bundle..."
./tooling/release_tools/target/release/release_tools bundle
./tooling/release_tools/target/release/release_tools verify
echo ""

echo "════════════════════════════════════════════════════════════════════════════"
echo -e "${GREEN}✅ PIPELINE COMPLETE!${NC}"
echo "════════════════════════════════════════════════════════════════════════════"
echo ""
echo "Outputs:"
echo "  • System validation:    dist/reports/system_validation_report.json"
echo "  • Canonicalized files:  dist/canonicalized/"
echo "  • Desired state:        system/canonical/desired/"
echo "  • Diff:                 system/canonical/diff/"
echo "  • Registry:             system/registry/"
echo "  • Release bundle:       dist/releases/release_bundle.json"
echo ""
echo "All tools executed successfully with deterministic output."
echo ""
