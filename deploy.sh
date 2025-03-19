#!/bin/bash

set -e

echo "🚀 Starting Deployment Process"

# Ensure project directory exists, clone if missing
if [ ! -d "/var/www/app-backend" ]; then
    echo "🔄 Cloning repository..."
    git clone git@github.com:douglashub/app-backend.git /var/www/app-backend
fi

# Navigate to the project directory
cd /var/www/app-backend || exit

echo "🔄 Pulling Latest Code with Rebase Strategy..."
git fetch origin main
git reset --hard origin/main
git pull --rebase origin main

echo "🔍 Checking Docker Installation..."
if ! command -v docker &> /dev/null; then
    echo "🚨 Docker is not installed. Installing now..."
    apt update && apt install -y docker.io
    systemctl start docker
    systemctl enable docker
fi

echo "🔍 Checking Docker Compose Installation..."
if ! command -v docker-compose &> /dev/null; then
    echo "🚨 Docker Compose not found. Installing now..."
    curl -L "https://github.com/docker/compose/releases/latest/download/docker-compose-$(uname -s)-$(uname -m)" -o /usr/local/bin/docker-compose
    chmod +x /usr/local/bin/docker-compose
fi

echo "📝 Setting Up Environment Variables..."
export APP_NAME="TransporteEscolar"
export APP_ENV="production"
export APP_KEY="base64:6ugcBE6DrMAx2Uu3wiNret+LHQDaQCRaxT2BiUM7zKk="
export APP_DEBUG="false"
export APP_URL="https://api.micasan.com.br"

# Internationalization
export APP_LOCALE="pt_BR"
export APP_FALLBACK_LOCALE="en"
export APP_FAKER_LOCALE="pt_BR"

# System Configuration
export APP_MAINTENANCE_DRIVER="file"
export PHP_CLI_SERVER_WORKERS="4"
export BCRYPT_ROUNDS="12"

# Logging
export LOG_CHANNEL="stack"
export LOG_STACK="single"
export LOG_DEPRECATIONS_CHANNEL="null"
export LOG_LEVEL="debug"

# ✅ Database Configuration
export DB_CONNECTION="pgsql"
export DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com"
export DB_PORT="25060"
export DB_DATABASE="defaultdb"
export DB_USERNAME="doadmin"
export DB_PASSWORD="AVNS_UnYjI2qmb8fsv0PgrYN"
export DB_SSLMODE="require"

# Session Configuration
export SESSION_DRIVER="database"
export SESSION_LIFETIME="120"
export SESSION_ENCRYPT="false"
export SESSION_PATH="/"
export SESSION_DOMAIN="api.micasan.com.br"

# Queue & Jobs
export QUEUE_CONNECTION="database"

# Caching
export CACHE_STORE="database"

# Broadcasting
export BROADCAST_CONNECTION="log"
export FILESYSTEM_DISK="local"

# Mail Configuration
export MAIL_MAILER="smtp"
export MAIL_HOST="smtp.mailserver.com"
export MAIL_PORT="587"
export MAIL_USERNAME="your-email@example.com"
export MAIL_PASSWORD="your-mail-password"
export MAIL_ENCRYPTION="tls"
export MAIL_FROM_ADDRESS="noreply@api.micasan.com.br"
export MAIL_FROM_NAME="TransporteEscolar"

# Additional Security
export TRUSTED_PROXIES="*"
export CORS_ALLOWED_ORIGINS="*"

# Redis Configuration
export REDIS_HOST="127.0.0.1"
export REDIS_PASSWORD="null"
export REDIS_PORT="6379"

# Docker Network
export DOCKER_NETWORK="laravel_network"

# Nginx Config
export NGINX_HOST="api.micasan.com.br"
export NGINX_PORT="80"

# Miscellaneous
export APP_TIMEZONE="America/Sao_Paulo"

# Persist Environment Variables for Future Sessions
echo "export DB_PASSWORD='AVNS_UnYjI2qmb8fsv0PgrYN'" >> ~/.bashrc
source ~/.bashrc

# Ensure .env exists
if [ ! -f ".env" ]; then
    echo "⚠️ .env file missing! Creating from example..."
    cp .env.example .env
    docker-compose exec app php artisan key:generate
fi

# ✅ Restart Laravel Config & Cache
echo "🔄 Clearing Laravel Cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache

# ✅ Install Dependencies
echo "📦 Installing Laravel Dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

echo "⚡ Installing Frontend Dependencies..."
docker-compose exec app npm install && npm run build

# ✅ Restart Docker Containers
echo "🐳 Restarting Docker Containers..."
docker-compose down
docker-compose up -d --build

# ✅ Run Database Migrations
echo "📊 Running Laravel Migrations..."
docker-compose exec app php artisan migrate --force

echo "🔄 Restarting Nginx..."
systemctl restart nginx

echo "✅ Deployment Completed Successfully!"
