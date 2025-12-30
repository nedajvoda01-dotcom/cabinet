#!/bin/bash
# Local execution script for Cabinet Platform

set -e

echo "=== Cabinet Platform - Local Startup ==="
echo ""

# Check if Docker is running
if ! docker info > /dev/null 2>&1; then
    echo "Error: Docker is not running. Please start Docker first."
    exit 1
fi

# Check if .env exists
if [ ! -f .env ]; then
    echo "Creating .env from .env.example..."
    cp .env.example .env
fi

# Create storage directory
echo "Creating storage directory..."
mkdir -p storage

# Start services
echo "Starting services with Docker Compose..."
docker compose up -d

echo ""
echo "Waiting for services to be ready..."
sleep 10

# Check health of services
echo ""
echo "=== Service Health Check ==="

check_service() {
    local name=$1
    local url=$2
    
    if curl -f -s "$url" > /dev/null 2>&1; then
        echo "✓ $name is running"
    else
        echo "✗ $name is not responding"
    fi
}

check_service "Platform" "http://localhost:8080/index.php"
check_service "Car Storage Adapter" "http://localhost:8081/health"
check_service "Pricing Adapter" "http://localhost:8082/health"
check_service "Automation Adapter" "http://localhost:8083/health"
check_service "Admin UI" "http://localhost:3000"
check_service "Public UI" "http://localhost:3001"

echo ""
echo "=== Cabinet Platform is Ready! ==="
echo ""
echo "Access points:"
echo "  Platform API:  http://localhost:8080/api/invoke"
echo "  Admin UI:      http://localhost:3000"
echo "  Public UI:     http://localhost:3001"
echo ""
echo "To stop: docker compose down"
echo "To view logs: docker compose logs -f"
echo ""
