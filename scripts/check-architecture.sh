#!/bin/bash
# Check for anti-patterns in the codebase
# These checks enforce architectural rules

set -e

ERRORS=0

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "========================================"
echo "Architectural Rules Validation"
echo "========================================"
echo ""

# Rule 1: No direct HTTP URLs to adapters in UI code
echo "1. Checking for direct adapter URLs in UI code..."
echo "   (UI should only call /api/invoke, never adapter URLs directly)"
echo ""

ADAPTER_URL_PATTERN='http://.*adapter-'
UI_FILES=$(find ui -type f \( -name "*.js" -o -name "*.html" -o -name "*.ts" -o -name "*.jsx" -o -name "*.tsx" \) 2>/dev/null || true)

if [ -n "$UI_FILES" ]; then
    VIOLATIONS=$(echo "$UI_FILES" | xargs grep -n "$ADAPTER_URL_PATTERN" 2>/dev/null || true)
    
    if [ -n "$VIOLATIONS" ]; then
        echo -e "   ${RED}✗ FAIL: Found direct adapter URLs in UI code:${NC}"
        echo "$VIOLATIONS" | while read -r line; do
            echo "      $line"
        done
        echo ""
        echo "   ${YELLOW}Fix: UI code must call /api/invoke, not adapter URLs directly${NC}"
        echo "   Example: fetch('/api/invoke', { method: 'POST', body: JSON.stringify({ capability: 'car.list', payload: {} }) })"
        echo ""
        ERRORS=$((ERRORS + 1))
    else
        echo -e "   ${GREEN}✓ PASS: No direct adapter URLs found in UI code${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠ SKIP: No UI files found${NC}"
fi

echo ""

# Rule 2: Warn about manual JSON editing (registry should use YAML)
echo "2. Checking for JSON files in registry (should use YAML as source of truth)..."
echo ""

JSON_FILES=$(find registry -name "*.json" 2>/dev/null || true)

if [ -n "$JSON_FILES" ]; then
    echo -e "   ${YELLOW}⚠ WARNING: Found JSON files in registry:${NC}"
    echo "$JSON_FILES" | while read -r file; do
        echo "      $file"
    done
    echo ""
    echo "   ${YELLOW}Note: YAML files are the source of truth. JSON files should be:${NC}"
    echo "   - Either removed (preferred)"
    echo "   - Or auto-generated from YAML (for backward compatibility)"
    echo ""
    echo "   To remove JSON files: rm registry/*.json"
    echo "   To regenerate from YAML: (implement a yaml-to-json converter if needed)"
    echo ""
    # This is a warning, not an error
else
    echo -e "   ${GREEN}✓ PASS: No JSON files in registry${NC}"
fi

echo ""

# Rule 3: No direct imports of Router.php (should use CapabilityExecutor)
echo "3. Checking for legacy Router.php usage..."
echo ""

ROUTER_USAGE=$(grep -rn "require.*Router\.php\|new Router(" platform/src platform/public 2>/dev/null | grep -v "RouterAdapter\|CapabilityRouter" || true)

if [ -n "$ROUTER_USAGE" ]; then
    echo -e "   ${RED}✗ FAIL: Found legacy Router.php usage:${NC}"
    echo "$ROUTER_USAGE" | while read -r line; do
        echo "      $line"
    done
    echo ""
    echo "   ${YELLOW}Fix: Use CapabilityExecutor instead of legacy Router${NC}"
    echo ""
    ERRORS=$((ERRORS + 1))
else
    echo -e "   ${GREEN}✓ PASS: No legacy Router usage found${NC}"
fi

echo ""

# Rule 4: Adapters should not make HTTP calls to other adapters
echo "4. Checking for adapter-to-adapter HTTP calls..."
echo ""

ADAPTER_PATTERN='http://.*adapter-|http://adapter-'
ADAPTER_FILES=$(find adapters -type f -name "*.php" 2>/dev/null || true)

if [ -n "$ADAPTER_FILES" ]; then
    ADAPTER_VIOLATIONS=$(echo "$ADAPTER_FILES" | xargs grep -n "$ADAPTER_PATTERN" 2>/dev/null | grep -v "ADAPTER_.*_URL" || true)
    
    if [ -n "$ADAPTER_VIOLATIONS" ]; then
        echo -e "   ${RED}✗ FAIL: Found adapter-to-adapter HTTP calls:${NC}"
        echo "$ADAPTER_VIOLATIONS" | while read -r line; do
            echo "      $line"
        done
        echo ""
        echo "   ${YELLOW}Fix: Adapters should not call other adapters directly${NC}"
        echo "   Use capability chaining through the core platform instead"
        echo ""
        ERRORS=$((ERRORS + 1))
    else
        echo -e "   ${GREEN}✓ PASS: No adapter-to-adapter HTTP calls found${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠ SKIP: No adapter files found${NC}"
fi

echo ""

# Rule 5: Check for hardcoded chain rules in CapabilityExecutor
echo "5. Checking for hardcoded chain rules..."
echo ""

if [ -f "platform/src/Core/CapabilityExecutor.php" ]; then
    # Look for hardcoded arrays like $internalCapabilities = [...] or $allowedChains = [...]
    HARDCODED=$(grep -n "\$internalCapabilities = \[" platform/src/Core/CapabilityExecutor.php 2>/dev/null || true)
    
    if [ -n "$HARDCODED" ]; then
        # Check if the array has actual values (not just empty or reading from config)
        HARDCODED_VALUES=$(grep -A 5 "\$internalCapabilities = \[" platform/src/Core/CapabilityExecutor.php | grep "'" || true)
        
        if [ -n "$HARDCODED_VALUES" ]; then
            echo -e "   ${RED}✗ FAIL: Found hardcoded chain rules:${NC}"
            echo "      platform/src/Core/CapabilityExecutor.php"
            echo ""
            echo "   ${YELLOW}Fix: Chain rules should be read from registry/capabilities.yaml${NC}"
            echo "   Use internal_only and allowed_parents fields"
            echo ""
            ERRORS=$((ERRORS + 1))
        else
            echo -e "   ${GREEN}✓ PASS: No hardcoded chain rules found${NC}"
        fi
    else
        echo -e "   ${GREEN}✓ PASS: No hardcoded chain rules found${NC}"
    fi
else
    echo -e "   ${YELLOW}⚠ SKIP: CapabilityExecutor.php not found${NC}"
fi

echo ""
echo "========================================"
echo "Validation Summary"
echo "========================================"

if [ $ERRORS -eq 0 ]; then
    echo -e "${GREEN}✓ All architectural rules passed!${NC}"
    echo ""
    echo "The codebase follows the canonical architecture:"
    echo "- UI code only calls /api/invoke"
    echo "- YAML is source of truth"
    echo "- CapabilityExecutor handles all invocations"
    echo "- Adapters communicate through the core"
    echo "- Chain rules are in registry"
    exit 0
else
    echo -e "${RED}✗ Found $ERRORS architectural violation(s)${NC}"
    echo ""
    echo "Please fix the violations above to maintain"
    echo "the canonical architecture."
    exit 1
fi
