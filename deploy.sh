#!/bin/bash

set -e  # Exit on any error

echo "🚀 Starting Deployment Process"

# 1) Ensure the repository exists
if [ ! -d "/var/www/app-backend" ]; then
    echo "🔄 Cloning repository..."
    git clone git@github.com:douglashub/app-backend.git /var/www/app-backend
fi

cd /var/www/app-backend || exit 1

echo "🔄 Pulling Latest Code..."
git fetch origin main
git reset --hard origin/main
git pull --rebase origin main

echo "🔍 Checking Docker Installation..."
if ! command -v docker &> /dev/null; then
    echo "🚨 Installing Docker..."
    apt update && apt install -y docker.io
    systemctl start docker
    systemctl enable docker
fi

echo "🔍 Checking Docker Compose Installation..."
if ! command -v docker-compose &> /dev/null; then
    echo "🚨 Installing Docker Compose..."
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" \
         -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
fi

echo "📝 Setting Up Environment Variables..."
if [ ! -f ".env" ]; then
    cp .env.example .env
fi

# Update Laravel database environment variables
sed -i "s|DB_HOST=.*|DB_HOST=db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com|" .env
sed -i "s|DB_PORT=.*|DB_PORT=25060|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=defaultdb|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=doadmin|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=AVNS_UnYjI2qmb8fsv0PgrYN|" .env

echo "✅ Using Dockerfile configuration for PHP-FPM (listen = 9000)"

echo "🐳 Stopping and Removing Old Containers..."
docker-compose down --rmi all --volumes --remove-orphans
docker system prune -af
docker volume prune -f

echo "🐳 Building and Restarting Docker Containers..."
docker-compose build --no-cache
docker-compose up -d --force-recreate --build

echo "🔄 Checking PostgreSQL Connection..."
DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com"
DB_PORT="25060"
MAX_ATTEMPTS=10
ATTEMPT=1

while [ $ATTEMPT -le $MAX_ATTEMPTS ]; do
    echo "  -> Attempt $ATTEMPT of $MAX_ATTEMPTS"
    if nc -zvw3 "$DB_HOST" "$DB_PORT"; then
        echo "✅ PostgreSQL is accessible!"
        break
    fi
    ATTEMPT=$((ATTEMPT+1))
    sleep 5
done

if [ $ATTEMPT -gt $MAX_ATTEMPTS ]; then
    echo "❌ Could not connect to PostgreSQL after $MAX_ATTEMPTS attempts."
    exit 1
fi

echo "⏳ Waiting for containers to be fully up..."
sleep 15

echo "🔥 Dropping and Recreating the Database..."
docker-compose exec -T app php artisan db:wipe --force

echo "📊 Running fresh migrations..."
docker-compose exec -T app php artisan migrate:fresh --force

echo "🌱 Seeding the database (optional)..."
docker-compose exec -T app php artisan db:seed --force

echo "🔄 Clearing Laravel caches..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:cache

echo "📦 Installing Composer dependencies..."
docker-compose exec -T app composer install --no-dev --optimize-autoloader

echo "⚡ Running npm install & build..."
docker-compose exec -T app bash -c "npm install && npm run build"

echo "🔄 Restarting Nginx..."
docker-compose restart nginx
sleep 5

echo "🔍 Testing HTTPS access..."
if curl -sk --head https://api.micasan.com.br | grep -q '200 OK'; then
    echo "✅ Application is accessible!"
else
    echo "❌ Application is not accessible. Nginx logs below:"
    docker-compose logs --tail=50 nginx
fi

echo "✅ Deployment Completed Successfully!"
