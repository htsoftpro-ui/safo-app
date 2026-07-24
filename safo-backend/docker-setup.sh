#!/bin/bash
set -e

echo "========================================="
echo "  Safo Backend — Docker Setup"
echo "========================================="

# Check if Docker is available
if ! command -v docker &> /dev/null; then
    echo "❌ Docker is not installed. Please install Docker first."
    exit 1
fi

if ! command -v docker-compose &> /dev/null && ! docker compose version &> /dev/null; then
    echo "❌ Docker Compose is not installed."
    exit 1
fi

# Copy .env if not exists
if [ ! -f .env ]; then
    echo "📋 Creating .env from .env.example..."
    cp .env.example .env
fi

# Generate app key
echo "🔑 Generating application key..."
APP_KEY=$(head -c 32 /dev/urandom | base64 | tr -d '/+=' | head -c 32)
sed -i "s/APP_KEY=$/APP_KEY=base64:$(echo -n "$APP_KEY" | base64)/" .env

# Build containers
echo "🔨 Building Docker containers..."
docker compose build

# Start services
echo "🚀 Starting services..."
docker compose up -d

# Wait for MySQL
echo "⏳ Waiting for MySQL to be ready..."
sleep 15

# Run migrations
echo "📦 Running migrations..."
docker compose exec -T app php artisan migrate --force

# Seed database
echo "🌱 Seeding database..."
docker compose exec -T app php artisan db:seed --force

# Cache config
echo "⚡ Caching configuration..."
docker compose exec -T app php artisan config:cache
docker compose exec -T app php artisan route:cache

# Health check
echo "🏥 Running health check..."
HEALTH=$(curl -s http://localhost:8080/api/v1/health 2>/dev/null)
if echo "$HEALTH" | grep -q '"status":"ok"'; then
    echo "✅ Health check passed: $HEALTH"
else
    echo "❌ Health check failed. Check logs with: docker compose logs app"
    exit 1
fi

echo ""
echo "========================================="
echo "  ✅ Safo Backend is running!"
echo "========================================="
echo ""
echo "  API:       http://localhost:8080/api/v1"
echo "  Health:    http://localhost:8080/api/v1/health"
echo "  MySQL:     localhost:3307"
echo "  Redis:     localhost:6380"
echo ""
echo "  Commands:"
echo "    docker compose logs -f        # View logs"
echo "    docker compose exec app php artisan tinker   # Tinker"
echo "    docker compose exec app php artisan test     # Run tests"
echo "    docker compose down           # Stop all"
echo ""
