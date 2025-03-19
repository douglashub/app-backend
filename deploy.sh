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

# Corrigir a configuração do PHP-FPM no Dockerfile antes de construir (já setamos 0660 ou 0666)
echo "🔧 Corrigindo a configuração do PHP-FPM no Dockerfile..."
if [ -f "Dockerfile" ]; then
    cp Dockerfile Dockerfile.bak

    sed -i '/# Configure PHP-FPM/,/# Nginx Configuration/c\\n# Configure PHP-FPM para usar apenas Unix Socket\\nRUN sed -i "s|listen = 127.0.0.1:9000|;listen = 127.0.0.1:9000|" /usr/local/etc/php-fpm.d/www.conf \\n    && sed -i "s|;listen = /run/php/php-fpm.sock|listen = /run/php/php-fpm.sock|" /usr/local/etc/php-fpm.d/www.conf \\n    && echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/www.conf \\n    && echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf \\n    && echo "listen.mode = 0666" >> /usr/local/etc/php-fpm.d/www.conf\\n\\n# Criar diretório do socket do PHP-FPM\\nRUN mkdir -p /run/php && chown -R www-data:www-data /run/php\\n\\n# Instalar killall para poder gerenciar processos\\nRUN apt-get update && apt-get install -y psmisc procps\\n\\n# Nginx Configuration' Dockerfile

    echo "✅ Dockerfile atualizado com sucesso para corrigir o PHP-FPM!"
else
    echo "❌ Dockerfile não encontrado! Verifique o caminho."
fi

echo "🔧 Modificando o arquivo zz-docker.conf para evitar conflitos de socket..."
cat > docker-php-entrypoint-override.sh << 'EOF'
#!/bin/sh
set -e

if [ -f /usr/local/etc/php-fpm.d/zz-docker.conf ]; then
  echo "⚡ Reconfigurando o arquivo zz-docker.conf..."
  sed -i 's|listen = 9000|;listen = 9000|g' /usr/local/etc/php-fpm.d/zz-docker.conf
fi

mkdir -p /run/php
chown -R www-data:www-data /run/php

exec "$@"
EOF

chmod +x docker-php-entrypoint-override.sh

# Criar um novo Dockerfile.override (para quando quiser usar)
cat > Dockerfile.override << 'EOF'
FROM app-backend-app:latest
COPY docker-php-entrypoint-override.sh /usr/local/bin/
ENTRYPOINT ["/usr/local/bin/docker-php-entrypoint-override.sh"]
CMD ["/usr/local/bin/start-container"]
EOF

echo "🐳 Stopping and Removing Old Containers..."
docker-compose down --volumes --remove-orphans

echo "🐳 Building and Restarting Docker Containers..."
docker-compose up -d --build

# =============================================
#  Checando Postgres
# =============================================
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

echo "⏳ Aguardando 15 segundos para os containers inicializarem completamente..."
sleep 15

# =============================================
#  Atualizar config do PHP-FPM (mas sem rodar outro PHP-FPM)
# =============================================
echo "🔧 Corrigindo o PHP-FPM diretamente no container..."
docker-compose exec -T app bash -c "
    echo '📋 Verificando configuração atual do PHP-FPM...'
    grep -r 'listen =' /usr/local/etc/php-fpm.d/
    
    echo '🔄 Ajustando listen.mode = 0666...'
    sed -i 's|listen.mode =.*|listen.mode = 0666|' /usr/local/etc/php-fpm.d/www.conf
    
    echo '🔄 Ajustando permissões em /run/php...'
    mkdir -p /run/php
    chown -R www-data:www-data /run/php

    # Reiniciar via supervisor
    echo '♻️ Reiniciando PHP-FPM pelo supervisor...'
    supervisorctl restart php-fpm
    
    sleep 3
    if [ -S /run/php/php-fpm.sock ]; then
        ls -la /run/php/
    else
        echo '❌ Falha ao criar o socket PHP-FPM!'
    fi
" || true

# =============================================
#  Limpar/corrigir caches Laravel
# =============================================
echo "🔄 Clearing Laravel Cache..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:cache

# =============================================
#  Composer install
# =============================================
echo "📦 Installing Laravel Dependencies..."
docker-compose exec -T app composer install --no-dev --optimize-autoloader

# =============================================
#  npm install + npm run build
# =============================================
echo "⚡ Checking if Node.js and npm are installed..."
docker-compose exec -T app bash -c "command -v node && command -v npm"
if [ $? -ne 0 ]; then
    echo "❌ Node.js and npm not found. Installing..."
    docker-compose exec -T app bash -c "curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && apt-get install -y nodejs npm"
fi

echo "⚡ Installing Frontend Dependencies..."
docker-compose exec -T app bash -c "npm install && npm run build"

# =============================================
#  Migrate
# =============================================
echo "📊 Running Laravel Migrations..."
docker-compose exec -T app php artisan migrate --force

# =============================================
#  Restart Nginx
# =============================================
echo "🔄 Restarting Nginx Container..."
docker-compose restart nginx
sleep 5

# =============================================
#  Verificações finais
# =============================================
echo "🔍 Executando verificação final do serviço..."
docker-compose exec -T app bash -c "ls -la /run/php/"
docker-compose exec -T app bash -c "stat -c '%a %U:%G' /run/php/php-fpm.sock || echo 'Socket não encontrado'"

echo "Verificando acesso ao site..."
if curl -sk --head https://api.micasan.com.br | grep -E 'HTTP/[0-9]\.[0-9] [2-3][0-9][0-9]'; then
    echo "✅ Site está acessível externamente!"
else
    echo "❌ Problemas ao acessar o site externamente! Verificando logs:"
    docker-compose logs --tail=50 nginx
    docker-compose logs --tail=20 app
fi

echo "✅ Deployment Completed Successfully!"
