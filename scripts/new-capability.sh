#!/bin/bash
# Add a new capability to the registry
# Usage: ./scripts/new-capability.sh <capability-name> <adapter-id>

set -e

CAPABILITY_NAME=$1
ADAPTER_ID=$2

if [ -z "$CAPABILITY_NAME" ] || [ -z "$ADAPTER_ID" ]; then
    echo "Usage: ./scripts/new-capability.sh <capability-name> <adapter-id>"
    echo ""
    echo "Examples:"
    echo "  ./scripts/new-capability.sh storage.backup storage-adapter"
    echo "  ./scripts/new-capability.sh catalog.search catalog-adapter"
    echo ""
    echo "The capability will be added to registry/capabilities.yaml"
    exit 1
fi

# Validate capability name format (namespace.action)
if ! echo "$CAPABILITY_NAME" | grep -qE '^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*(\.[a-z][a-z0-9_]*)*$'; then
    echo "Error: Capability name must be in format 'namespace.action' (e.g., 'storage.backup')"
    echo "       Use lowercase letters, numbers, and underscores only"
    exit 1
fi

REGISTRY_FILE="./registry/capabilities.yaml"

if [ ! -f "$REGISTRY_FILE" ]; then
    echo "Error: Registry file not found: $REGISTRY_FILE"
    exit 1
fi

# Check if capability already exists
if grep -q "^  $CAPABILITY_NAME:" "$REGISTRY_FILE"; then
    echo "Error: Capability '$CAPABILITY_NAME' already exists in $REGISTRY_FILE"
    exit 1
fi

# Check if adapter exists in adapters.yaml
if ! grep -q "^  $ADAPTER_ID:" ./registry/adapters.yaml 2>/dev/null; then
    echo "Warning: Adapter '$ADAPTER_ID' not found in registry/adapters.yaml"
    echo "         Make sure to register the adapter first with ./scripts/new-adapter.sh"
    read -p "Continue anyway? [y/N] " -n 1 -r
    echo
    if [[ ! $REPLY =~ ^[Yy]$ ]]; then
        exit 1
    fi
fi

echo "Adding capability: $CAPABILITY_NAME"
echo "Adapter: $ADAPTER_ID"
echo "=================================="

# Ask for additional details
read -p "Description: " DESCRIPTION
read -p "Is this an internal-only capability? [y/N] " -n 1 -r IS_INTERNAL
echo

# Prepare capability entry
CAPABILITY_ENTRY="
  $CAPABILITY_NAME:
    adapter: $ADAPTER_ID
    description: \"$DESCRIPTION\"
    internal_only: false
    allowed_fields:
      - id
      - created_at
      - updated_at"

# If internal-only, ask for allowed parents
if [[ $IS_INTERNAL =~ ^[Yy]$ ]]; then
    echo ""
    echo "This is an internal-only capability."
    echo "It can only be called from specific parent capabilities."
    echo "Enter allowed parent capabilities (one per line, empty line to finish):"
    
    ALLOWED_PARENTS=()
    while true; do
        read -p "  Parent capability: " PARENT
        if [ -z "$PARENT" ]; then
            break
        fi
        ALLOWED_PARENTS+=("$PARENT")
    done
    
    if [ ${#ALLOWED_PARENTS[@]} -eq 0 ]; then
        echo "Error: Internal-only capabilities must have at least one allowed parent"
        exit 1
    fi
    
    # Update capability entry with internal_only: true and allowed_parents
    CAPABILITY_ENTRY="
  $CAPABILITY_NAME:
    adapter: $ADAPTER_ID
    description: \"$DESCRIPTION\"
    internal_only: true
    allowed_parents:"
    
    for PARENT in "${ALLOWED_PARENTS[@]}"; do
        CAPABILITY_ENTRY="$CAPABILITY_ENTRY
      - $PARENT"
    done
    
    CAPABILITY_ENTRY="$CAPABILITY_ENTRY
    allowed_fields:
      - id
      - created_at
      - updated_at"
fi

# Add capability to registry file
# Append to end of file (assumes YAML structure is maintained)
echo "$CAPABILITY_ENTRY" >> "$REGISTRY_FILE"

echo ""
echo "✓ Added capability to $REGISTRY_FILE"
echo "  (Appended to end of file)"
echo ""
echo "Next steps:"
echo "==========="
echo "1. Review and edit $REGISTRY_FILE to:"
echo "   - Adjust the allowed_fields list"
echo "   - Add any additional configuration"
echo ""
echo "2. Implement the capability handler in adapters/$ADAPTER_ID/invoke.php:"
echo ""
# Convert capability name to camelCase function name
FUNC_NAME=$(echo "$CAPABILITY_NAME" | sed 's/\./ /g' | awk '{for(i=1;i<=NF;i++){$i=toupper(substr($i,1,1)) substr($i,2)}}1' | sed 's/ //g')
echo "   case '$CAPABILITY_NAME':"
echo "       \$result = handle$FUNC_NAME(\$payload);"
echo "       break;"
echo ""
echo "3. Add capability to UI in registry/ui.yaml if needed:"
echo ""
echo "   admin:"
echo "     allowed_capabilities:"
echo "       - $CAPABILITY_NAME"
echo ""
echo "4. Validate registry: ./scripts/validate-registry.sh"
echo ""
echo "5. Restart platform: docker-compose restart platform"
