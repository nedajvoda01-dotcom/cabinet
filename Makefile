.PHONY: structure structure-clean install test test-arch test-contracts test-compat test-e2e start clean help

# Generate structure documentation
structure:
	tree -a --noreport . > STRUCTURE.txt

structure-clean:
	tree -a --noreport . | grep -v '\.gitkeep' > STRUCTURE.clean.txt

# Install dependencies
install:
	@echo "📦 Installing dependencies..."
	composer install
	cd ui/desktop && npm install
	@echo "✅ Dependencies installed"

# Run all tests
test:
	@echo "🧪 Running all acceptance tests..."
	php tests/run-all.php

# Run individual test suites
test-arch:
	@echo "🏗️  Running architectural boundary tests..."
	php tests/architecture/boundary-tests.php

test-contracts:
	@echo "📋 Running contract tests..."
	php tests/contracts/parity-tests.php
	php tests/contracts/smoke-tests.php

test-compat:
	@echo "🔍 Running compatibility checker..."
	php delivery/compat/compatibility-checker.php

test-e2e:
	@echo "🚀 Running E2E smoke tests..."
	php tests/e2e-smoke/critical-path.php

# Start the application
start:
	@echo "🚀 Starting Cabinet Platform Monorepo..."
	./scripts/start.sh

# Clean build artifacts
clean:
	@echo "🧹 Cleaning build artifacts..."
	rm -rf vendor/
	rm -rf ui/desktop/node_modules/
	rm -rf ui/desktop/dist/
	rm -rf platform/vendor/
	rm -rf data/*.db
	@echo "✅ Cleaned"

# Show help
help:
	@echo "Cabinet Platform Monorepo - Available Commands"
	@echo ""
	@echo "📦 Installation:"
	@echo "  make install          Install all dependencies"
	@echo ""
	@echo "🧪 Testing:"
	@echo "  make test             Run all acceptance tests"
	@echo "  make test-arch        Run architectural boundary tests"
	@echo "  make test-contracts   Run contract tests"
	@echo "  make test-compat      Run compatibility checker"
	@echo "  make test-e2e         Run E2E smoke tests"
	@echo ""
	@echo "🚀 Development:"
	@echo "  make start            Start the application"
	@echo "  make clean            Clean build artifacts"
	@echo ""
	@echo "📚 Documentation:"
	@echo "  make structure        Generate structure documentation"
	@echo "  make help             Show this help message"
