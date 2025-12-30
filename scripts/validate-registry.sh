#!/bin/bash
# Registry Validation Script
# MVP Step 3: Validates registry YAML files for consistency and correctness

set -e

REGISTRY_PATH="${REGISTRY_PATH:-./registry}"
ERRORS=0

echo "========================================"
echo "Registry Validation"
echo "========================================"
echo ""

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if YAML files exist
echo "1. Checking for required registry files..."
REQUIRED_FILES=(
    "adapters.yaml"
    "capabilities.yaml"
    "policy.yaml"
    "ui.yaml"
    "result_profiles.yaml"
)

for file in "${REQUIRED_FILES[@]}"; do
    if [ -f "$REGISTRY_PATH/$file" ]; then
        echo -e "   ${GREEN}✓${NC} $file exists"
    else
        echo -e "   ${RED}✗${NC} $file is missing"
        ERRORS=$((ERRORS + 1))
    fi
done

echo ""
echo "2. Validating YAML syntax..."

# Check if yaml command is available (from yaml or yq package)
if command -v yamllint &> /dev/null; then
    for file in "${REQUIRED_FILES[@]}"; do
        if [ -f "$REGISTRY_PATH/$file" ]; then
            if yamllint -d relaxed "$REGISTRY_PATH/$file" 2>/dev/null; then
                echo -e "   ${GREEN}✓${NC} $file syntax is valid"
            else
                echo -e "   ${RED}✗${NC} $file has syntax errors"
                ERRORS=$((ERRORS + 1))
            fi
        fi
    done
else
    echo -e "   ${YELLOW}⚠${NC} yamllint not installed, skipping YAML syntax validation"
    echo "   Install with: pip install yamllint"
fi

echo ""
echo "3. Validating cross-references..."

# Use PHP to validate cross-references
php -r '
$registryPath = getenv("REGISTRY_PATH") ?: "./registry";

function loadYaml($file) {
    global $registryPath;
    $path = "$registryPath/$file";
    if (!file_exists($path)) {
        return null;
    }
    if (function_exists("yaml_parse_file")) {
        return yaml_parse_file($path);
    }
    // Fallback: try to parse simple YAML manually (very basic)
    echo "   Warning: YAML extension not available, validation limited\n";
    return null;
}

$errors = 0;

// Load all registries
$adapters = loadYaml("adapters.yaml");
$capabilities = loadYaml("capabilities.yaml");
$policy = loadYaml("policy.yaml");
$ui = loadYaml("ui.yaml");
$profiles = loadYaml("result_profiles.yaml");

if (!$adapters || !$capabilities || !$policy || !$ui) {
    echo "   Error: Could not load registry files\n";
    exit(1);
}

// Check: Every capability references an existing adapter
echo "   Checking capability → adapter references...\n";
foreach ($capabilities["capabilities"] ?? [] as $capName => $capConfig) {
    $adapterId = $capConfig["adapter"] ?? null;
    if (!$adapterId) {
        echo "   ✗ Capability \"$capName\" has no adapter specified\n";
        $errors++;
        continue;
    }
    if (!isset($adapters["adapters"][$adapterId])) {
        echo "   ✗ Capability \"$capName\" references non-existent adapter \"$adapterId\"\n";
        $errors++;
    }
}

// Check: Every UI allowed_capability exists
echo "   Checking UI → capability references...\n";
foreach ($ui["ui"] ?? [] as $uiName => $uiConfig) {
    foreach ($uiConfig["allowed_capabilities"] ?? [] as $capName) {
        if (!isset($capabilities["capabilities"][$capName])) {
            echo "   ✗ UI \"$uiName\" references non-existent capability \"$capName\"\n";
            $errors++;
        }
    }
}

// Check: internal_only capabilities have allowed_parents
echo "   Checking internal_only capabilities...\n";
foreach ($capabilities["capabilities"] ?? [] as $capName => $capConfig) {
    $internalOnly = $capConfig["internal_only"] ?? false;
    $allowedParents = $capConfig["allowed_parents"] ?? [];
    
    if ($internalOnly && empty($allowedParents)) {
        echo "   ✗ Internal-only capability \"$capName\" has no allowed_parents\n";
        $errors++;
    }
    
    // Check that allowed_parents are valid capabilities
    foreach ($allowedParents as $parentCap) {
        if (!isset($capabilities["capabilities"][$parentCap])) {
            echo "   ✗ Capability \"$capName\" references non-existent parent \"$parentCap\"\n";
            $errors++;
        }
    }
}

// Check: UI result_profile references exist
if ($profiles) {
    echo "   Checking UI → result_profile references...\n";
    foreach ($ui["ui"] ?? [] as $uiName => $uiConfig) {
        $profileName = $uiConfig["result_profile"] ?? null;
        if ($profileName) {
            $profileMapping = $profiles["ui_profiles"][$uiName] ?? null;
            if (!$profileMapping && !isset($profiles["profiles"][$profileName])) {
                echo "   ✗ UI \"$uiName\" references non-existent profile \"$profileName\"\n";
                $errors++;
            }
        }
    }
}

if ($errors > 0) {
    echo "\n   Found $errors validation errors\n";
    exit(1);
}

echo "   All cross-references are valid\n";
exit(0);
'

if [ $? -ne 0 ]; then
    ERRORS=$((ERRORS + 1))
fi

echo ""
echo "========================================"
echo "Validation Summary"
echo "========================================"

if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ All validations passed!${NC}"
    echo ""
    echo "Registry is valid and consistent."
    exit 0
else
    echo -e "${RED}✗ Validation failed with $ERRORS error(s)${NC}"
    echo ""
    echo "Please fix the errors above before proceeding."
    exit 1
fi
