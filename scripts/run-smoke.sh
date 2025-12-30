#!/bin/bash
# Run smoke tests wrapper
# Convenience script that wraps tests/run-smoke-tests.sh with proper configuration

set -e

# Default configuration
PLATFORM_URL="${PLATFORM_URL:-http://localhost:8080}"
ADMIN_API_KEY="${API_KEY_ADMIN:-admin_secret_key_12345}"
PUBLIC_API_KEY="${API_KEY_PUBLIC:-public_secret_key_67890}"

# Color codes
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo -e "${BLUE}========================================"
echo "Cabinet Platform - Smoke Tests"
echo -e "========================================${NC}"
echo ""
echo "Platform URL: $PLATFORM_URL"
echo "Admin API Key: ${ADMIN_API_KEY:0:20}..."
echo "Public API Key: ${PUBLIC_API_KEY:0:20}..."
echo ""

# Check if platform is accessible
echo -n "Checking platform availability... "
if curl -s -f --max-time 5 "$PLATFORM_URL/api/version" > /dev/null 2>&1; then
    echo -e "${GREEN}✓ Platform is accessible${NC}"
else
    echo -e "${RED}✗ Platform is not accessible${NC}"
    echo ""
    echo "Please ensure:"
    echo "  1. Platform is running: docker-compose up -d"
    echo "  2. Platform URL is correct: $PLATFORM_URL"
    echo ""
    exit 1
fi

echo ""
echo -e "${BLUE}Running smoke tests...${NC}"
echo ""

# Run smoke tests (stay in current directory, use subshell)
(
cd tests && \
PLATFORM_URL="$PLATFORM_URL/api/invoke" \
API_KEY_ADMIN="$ADMIN_API_KEY" \
API_KEY_PUBLIC="$PUBLIC_API_KEY" \
./run-smoke-tests.sh
)

RESULT=$?

echo ""
if [ $RESULT -eq 0 ]; then
    echo -e "${GREEN}========================================${NC}"
    echo -e "${GREEN}✓ All smoke tests passed!${NC}"
    echo -e "${GREEN}========================================${NC}"
else
    echo -e "${RED}========================================${NC}"
    echo -e "${RED}✗ Some smoke tests failed!${NC}"
    echo -e "${RED}========================================${NC}"
    echo ""
    echo "Check the output above for details."
fi

exit $RESULT
