#!/bin/bash

set -e  # Para o script caso qualquer comando retorne erro

echo "🚀 Starting Deployment Process"

# 1) Garantir que o repositório existe
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

# Ajusta as variáveis de ambiente do Laravel
sed -i "s|DB_HOST=.*|DB_HOST=db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com|" .env
sed -i "s|DB_PORT=.*|DB_PORT=25060|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=defaultdb|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=doadmin|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=AVNS_UnYjI2qmb8fsv0PgrYN|" .env

echo "🔧 Corrigindo a configuração do PHP-FPM no Dockerfile (listen.mode = 0666)..."
# Faz backup do Dockerfile original
cp Dockerfile Dockerfile.bak

# Remove qualquer instrução duplicada de listen.mode e insere "listen.mode = 0666"
sed -i '/listen.mode/c\    && echo "listen.mode = 0666" >> /usr/local/etc/php-fpm.d/www.conf \\' Dockerfile

echo "✅ Dockerfile atualizado com sucesso!"

echo "🐳 Stopping and Removing Old Containers..."
docker-compose down --volumes --remove-orphans

echo "🐳 Building and Restarting Docker Containers..."
docker-compose up -d --build

echo "🔄 Checking PostgreSQL Connection..."
DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com"
DB_PORT="25060"
MAX_ATTEMPTS=10
ATTEMPT=1

while [ $ATTEMPT -le $MAX_ATTEMPTS ]; do
    echo "  -> Tentativa $ATTEMPT de $MAX_ATTEMPTS"
    if nc -zvw3 "$DB_HOST" "$DB_PORT"; then
        echo "✅ Banco de dados PostgreSQL acessível!"
        break
    fi
    ATTEMPT=$((ATTEMPT+1))
    sleep 5
done

if [ $ATTEMPT -gt $MAX_ATTEMPTS ]; then
    echo "❌ Não foi possível conectar ao PostgreSQL depois de $MAX_ATTEMPTS tentativas."
    exit 1
fi

echo "⏳ Aguardando containers subirem completamente..."
sleep 15

echo "🔄 Limpando caches do Laravel..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:cache

echo "📦 Install composer dependencies..."
docker-compose exec -T app composer install --no-dev --optimize-autoloader

echo "⚡ npm install & build..."
docker-compose exec -T app bash -c "npm install && npm run build"

echo "📊 Running migrations..."
docker-compose exec -T app php artisan migrate --force

echo "🔄 Restarting Nginx..."
docker-compose restart nginx
sleep 5

echo "🔍 Testando acesso via HTTPS..."
if curl -sk --head https://api.micasan.com.br | grep -q '200 OK'; then
    echo "✅ Aplicação está acessível!"
else
    echo "❌ Ainda não acessível. Logs do NGINX abaixo:"
    docker-compose logs --tail=50 nginx
fi

echo "✅ Deployment Completed Successfully!"
