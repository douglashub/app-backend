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

echo "🐳 Stopping and Removing Old Containers..."
docker-compose down --volumes --remove-orphans

echo "🐳 Building and Restarting Docker Containers..."
docker-compose up -d --build

# 🔍 Verificação do PHP-FPM e Socket
echo "🔍 Verificando configuração do PHP-FPM..."
# Aguardar até que os containers estejam totalmente inicializados
sleep 10

# Verificar se o diretório /run/php existe no container app
echo "Verificando diretório do socket PHP-FPM..."
if docker-compose exec app sh -c "[ -d /run/php ]"; then
    echo "✅ Diretório /run/php existe no container app"
else
    echo "❌ Diretório /run/php não encontrado! Criando..."
    docker-compose exec app sh -c "mkdir -p /run/php && chown -R www-data:www-data /run/php"
fi

# Verificar se o socket do PHP-FPM está funcionando
echo "Verificando socket PHP-FPM..."
if docker-compose exec app sh -c "ls -la /run/php/php-fpm.sock 2>/dev/null"; then
    echo "✅ Socket PHP-FPM existe e está configurado corretamente!"
else
    echo "⚠️ Socket PHP-FPM não encontrado! Verificando configuração..."
    
    # Verificar configuração do PHP-FPM
    docker-compose exec app sh -c "grep -r 'listen = ' /usr/local/etc/php-fpm.d/"
    
    # Corrigir configuração se necessário
    echo "🔧 Corrigindo configuração do PHP-FPM..."
    docker-compose exec app sh -c "sed -i \"s|listen = 127.0.0.1:9000|;listen = 127.0.0.1:9000|\" /usr/local/etc/php-fpm.d/www.conf \
        && sed -i \"s|;listen = /run/php/php-fpm.sock|listen = /run/php/php-fpm.sock|\" /usr/local/etc/php-fpm.d/www.conf \
        && mkdir -p /run/php \
        && chown -R www-data:www-data /run/php"
    
    # Reiniciar o PHP-FPM
    echo "🔄 Reiniciando PHP-FPM..."
    docker-compose exec app sh -c "killall php-fpm || true"
    docker-compose exec app sh -c "/usr/local/sbin/php-fpm --nodaemonize &"
    sleep 5
    
    # Verificar novamente
    if docker-compose exec app sh -c "ls -la /run/php/php-fpm.sock 2>/dev/null"; then
        echo "✅ Socket PHP-FPM agora está funcionando corretamente!"
    else
        echo "❌ Ainda há problemas com o socket PHP-FPM. Verificando logs..."
        docker-compose logs app
        echo "⚠️ Continuando a implantação, mas podem ocorrer problemas com o PHP-FPM."
    fi
fi

# Verificar se o Nginx pode acessar o socket
echo "Verificando acesso do Nginx ao socket PHP-FPM..."
if docker-compose exec nginx sh -c "ls -la /run/php/php-fpm.sock 2>/dev/null"; then
    echo "✅ Nginx pode acessar o socket PHP-FPM!"
else
    echo "❌ Nginx não consegue acessar o socket PHP-FPM! Verificando montagem de volumes..."
    docker-compose exec nginx sh -c "ls -la /run/php/"
    echo "⚠️ A comunicação entre Nginx e PHP-FPM pode estar comprometida."
fi

# ✅ Ensure Database is Reachable Before Running Migrations
MAX_ATTEMPTS=10
ATTEMPT=0
DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com"
DB_PORT="25060"

echo "🔄 Checking PostgreSQL Connection..."
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do
    echo "Checking PostgreSQL connection (Attempt $((ATTEMPT+1))/$MAX_ATTEMPTS)"
    if nc -zv $DB_HOST $DB_PORT; then
        echo "✅ PostgreSQL is reachable."
        break
    fi
    sleep 5
    ATTEMPT=$((ATTEMPT+1))
done

if [ $ATTEMPT -eq $MAX_ATTEMPTS ]; then
    echo "❌ Failed to connect to PostgreSQL after $MAX_ATTEMPTS attempts."
    exit 1
fi

# ✅ Restart Laravel Config & Cache
echo "🔄 Clearing Laravel Cache..."
docker-compose exec app php artisan config:clear
docker-compose exec app php artisan cache:clear
docker-compose exec app php artisan config:cache

# ✅ Install Dependencies
echo "📦 Installing Laravel Dependencies..."
docker-compose exec app composer install --no-dev --optimize-autoloader

# ✅ Ensure `npm` is installed before running frontend dependencies
echo "⚡ Checking if Node.js and npm are installed..."
docker-compose exec app sh -c "command -v node && command -v npm"
if [ $? -ne 0 ]; then
    echo "❌ Node.js and npm not found. Installing..."
    docker-compose exec app sh -c "curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && apt-get install -y nodejs npm"
fi

# ✅ Install Frontend Dependencies
echo "⚡ Installing Frontend Dependencies..."
docker-compose exec app sh -c "npm install && npm run build"

# ✅ Run Database Migrations
echo "📊 Running Laravel Migrations..."
docker-compose exec app php artisan migrate --force

# ✅ Start Laravel PHP server if necessary (SEM `pgrep`)
echo "🚀 Ensuring Laravel is running..."
docker-compose exec app sh -c "
if ps aux | grep '[p]hp artisan serve' > /dev/null; then
    echo '✅ Laravel PHP server is already running.'
else
    echo '🔄 Starting Laravel PHP server...'
    nohup php artisan serve --host=0.0.0.0 --port=8000 > storage/logs/laravel-server.log 2>&1 &
    disown
fi"

# ✅ Restart Nginx (inside container, not host service)
echo "🔄 Restarting Nginx Container..."
docker-compose restart nginx

# Verificação final
echo "🔍 Executando verificação final do serviço..."
# Verificar se o site está acessível externamente
echo "Verificando acesso ao site..."
if curl -s --head https://api.micasan.com.br | grep "200 OK"; then
    echo "✅ Site está acessível externamente! A comunicação Nginx-PHP-FPM está funcionando corretamente!"
else
    echo "❌ Problemas ao acessar o site externamente! Verificando logs:"
    docker-compose logs nginx
    docker-compose logs app
fi

echo "✅ Deployment Completed Successfully!"