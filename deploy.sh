#!/bin/bash

set -e  # Exit script on any error

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
if [ ! -f ".env" ]; then
    echo "⚠️ .env file missing! Creating from example..."
    cp .env.example .env
fi

# Ensure database credentials are set in .env
sed -i "s|DB_HOST=.*|DB_HOST=db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com|" .env
sed -i "s|DB_PORT=.*|DB_PORT=25060|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=defaultdb|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=doadmin|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=AVNS_UnYjI2qmb8fsv0PgrYN|" .env

# ✅ Restart Laravel Config & Cache
echo "🔄 Clearing Laravel Cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache

# ✅ Install Dependencies
echo "📦 Installing Laravel Dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

echo "⚡ Installing Frontend Dependencies..."
docker-compose exec app npm install
docker-compose exec app npm run build

# ✅ Ensure Database is Reachable
MAX_ATTEMPTS=10
ATTEMPT=0
DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com"
DB_PORT="25060"

echo "🔄 Checking PostgreSQL Connection..."
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    echo "Checking PostgreSQL connection (Attempt $((ATTEMPT+1))/$MAX_ATTEMPTS)"
    nc -zv $DB_HOST $DB_PORT && break
    sleep 5
    ATTEMPT=$((ATTEMPT+1))
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "❌ Failed to connect to PostgreSQL after $MAX_ATTEMPTS attempts."
    exit 1
fi

# ✅ Run Database Migrations
echo "📊 Running Laravel Migrations..."
docker-compose exec app php artisan migrate --force

# ✅ Restart Docker Containers
echo "🐳 Restarting Docker Containers..."
docker-compose down
docker-compose up -d --build

# ✅ Restarting Nginx Container (instead of host service)
echo "🔄 Restarting Nginx Container..."
docker-compose restart nginx

echo "✅ Deployment Completed Successfully!"
#!/bin/bash

set -e  # Exit script on any error

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
if [ ! -f ".env" ]; then
    echo "⚠️ .env file missing! Creating from example..."
    cp .env.example .env
fi

# Ensure database credentials are set in .env
sed -i "s|DB_HOST=.*|DB_HOST=db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com|" .env
sed -i "s|DB_PORT=.*|DB_PORT=25060|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=defaultdb|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=doadmin|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=AVNS_UnYjI2qmb8fsv0PgrYN|" .env

# ✅ Restart Laravel Config & Cache
echo "🔄 Clearing Laravel Cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache

# ✅ Install Dependencies
echo "📦 Installing Laravel Dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

echo "⚡ Installing Frontend Dependencies..."
docker-compose exec app npm install
docker-compose exec app npm run build

# ✅ Ensure Database is Reachable
MAX_ATTEMPTS=10
ATTEMPT=0
DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com"
DB_PORT="25060"

echo "🔄 Checking PostgreSQL Connection..."
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    echo "Checking PostgreSQL connection (Attempt $((ATTEMPT+1))/$MAX_ATTEMPTS)"
    nc -zv $DB_HOST $DB_PORT && break
    sleep 5
    ATTEMPT=$((ATTEMPT+1))
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "❌ Failed to connect to PostgreSQL after $MAX_ATTEMPTS attempts."
    exit 1
fi

# ✅ Run Database Migrations
echo "📊 Running Laravel Migrations..."
docker-compose exec app php artisan migrate --force

# ✅ Restart Docker Containers
echo "🐳 Restarting Docker Containers..."
docker-compose down
docker-compose up -d --build

# ✅ Restarting Nginx Container (instead of host service)
echo "🔄 Restarting Nginx Container..."
docker-compose restart nginx

echo "✅ Deployment Completed Successfully!"
