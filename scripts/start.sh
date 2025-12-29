#!/usr/bin/env bash
# Cabinet Platform Monorepo - One Command Startup Script

set -e

echo "🚀 Starting Cabinet Platform Monorepo..."
echo ""

# Check for Docker
if ! command -v docker-compose &> /dev/null && ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

# Create data directory if it doesn't exist
mkdir -p data

# Check if composer dependencies are installed
if [ ! -d "vendor" ]; then
    echo "📦 Installing PHP dependencies..."
    docker run --rm -v $(pwd):/app -w /app composer:latest install
fi

# Check if UI dependencies are installed
if [ ! -d "ui/desktop/node_modules" ]; then
    echo "📦 Installing UI dependencies..."
    cd ui/desktop && npm install && cd ../..
fi

echo ""
echo "🐳 Starting services with Docker Compose..."
echo ""

# Start services
if command -v docker-compose &> /dev/null; then
    docker-compose up
else
    docker compose up
fi
