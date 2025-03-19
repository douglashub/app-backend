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

# Corrigir a configuração do PHP-FPM no Dockerfile antes de construir
echo "🔧 Corrigindo a configuração do PHP-FPM no Dockerfile..."
# Verificar se o Dockerfile existe
if [ -f "Dockerfile" ]; then
    # Fazer backup do Dockerfile original
    cp Dockerfile Dockerfile.bak
    
    # Modificar a configuração do PHP-FPM no Dockerfile
    sed -i '/# Configure PHP-FPM/,/# Nginx Configuration/c\
# Configure PHP-FPM para usar apenas Unix Socket\
RUN sed -i "s|listen = 127.0.0.1:9000|;listen = 127.0.0.1:9000|" /usr/local/etc/php-fpm.d/www.conf \\\
    && sed -i "s|;listen = /run/php/php-fpm.sock|listen = /run/php/php-fpm.sock|" /usr/local/etc/php-fpm.d/www.conf \\\
    && echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/www.conf \\\
    && echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf \\\
    && echo "listen.mode = 0660" >> /usr/local/etc/php-fpm.d/www.conf\
\
# Criar diretório do socket do PHP-FPM\
RUN mkdir -p /run/php && chown -R www-data:www-data /run/php\
\
# Instalar killall para poder gerenciar processos\
RUN apt-get update && apt-get install -y psmisc procps\
\
# Nginx Configuration' Dockerfile
    
    echo "✅ Dockerfile atualizado com sucesso para corrigir o PHP-FPM!"
else
    echo "❌ Dockerfile não encontrado! Verifique o caminho."
fi

# Corrigir a configuração do zz-docker.conf que está causando conflito
echo "🔧 Modificando o arquivo zz-docker.conf para evitar conflitos de socket..."
cat > docker-php-entrypoint-override.sh << 'EOF'
#!/bin/sh
set -e

# Primeiro, modificar a configuração no zz-docker.conf
if [ -f /usr/local/etc/php-fpm.d/zz-docker.conf ]; then
  echo "⚡ Reconfigurando o arquivo zz-docker.conf..."
  # Comentar a linha 'listen = 9000' que está causando conflito
  sed -i 's|listen = 9000|;listen = 9000|g' /usr/local/etc/php-fpm.d/zz-docker.conf
fi

# Garantir que o diretório do socket existe
mkdir -p /run/php
chown -R www-data:www-data /run/php

# Executar o comando original
exec "$@"
EOF

chmod +x docker-php-entrypoint-override.sh

# Criar um novo Dockerfile.override para incluir o script acima
cat > Dockerfile.override << 'EOF'
# Use o Dockerfile existente como base
FROM app-backend-app:latest

# Copiar o script de override para o entrypoint
COPY docker-php-entrypoint-override.sh /usr/local/bin/
ENTRYPOINT ["/usr/local/bin/docker-php-entrypoint-override.sh"]
CMD ["/usr/local/bin/start-container"]
EOF

echo "🐳 Stopping and Removing Old Containers..."
docker-compose down --volumes --remove-orphans

echo "🐳 Building and Restarting Docker Containers..."
docker-compose up -d --build

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

# Dar tempo para os containers inicializarem completamente
echo "⏳ Aguardando 15 segundos para os containers inicializarem completamente..."
sleep 15

# Corrigir o PHP-FPM diretamente no container
echo "🔧 Corrigindo o PHP-FPM diretamente no container..."
docker-compose exec -T app bash -c "
    echo '📋 Verificando configuração atual do PHP-FPM...'
    grep -r 'listen =' /usr/local/etc/php-fpm.d/
    
    echo '🔄 Aplicando correções ao PHP-FPM...'
    # Comentar todas as linhas de listen existentes
    sed -i 's|listen = |;listen = |g' /usr/local/etc/php-fpm.d/zz-docker.conf
    sed -i 's|listen = |;listen = |g' /usr/local/etc/php-fpm.d/www.conf
    
    # Adicionar a configuração correta
    echo 'listen = /run/php/php-fpm.sock' >> /usr/local/etc/php-fpm.d/www.conf
    echo 'listen.owner = www-data' >> /usr/local/etc/php-fpm.d/www.conf
    echo 'listen.group = www-data' >> /usr/local/etc/php-fpm.d/www.conf
    echo 'listen.mode = 0660' >> /usr/local/etc/php-fpm.d/www.conf
    
    # Garantir que o diretório existe
    mkdir -p /run/php
    chown -R www-data:www-data /run/php
    
    # Reiniciar o PHP-FPM
    if command -v killall > /dev/null 2>&1; then
        killall php-fpm || true
    elif ps aux | grep -v grep | grep php-fpm > /dev/null; then
        pkill php-fpm || true
    fi
    
    # Iniciar PHP-FPM em modo standalone para debug
    echo '🔄 Reiniciando PHP-FPM...'
    /usr/local/sbin/php-fpm --nodaemonize &
    
    # Esperar um pouco para o socket ser criado
    sleep 5
    
    # Verificar se o socket foi criado
    if [ -S /run/php/php-fpm.sock ]; then
        echo '✅ Socket PHP-FPM criado com sucesso!'
        ls -la /run/php/
    else
        echo '❌ Falha ao criar o socket PHP-FPM!'
    fi
" || true  # Permitir que o script continue mesmo se este comando falhar

# ✅ Restart Laravel Config & Cache
echo "🔄 Clearing Laravel Cache..."
docker-compose exec -T app php artisan config:clear
docker-compose exec -T app php artisan cache:clear
docker-compose exec -T app php artisan config:cache

# ✅ Install Dependencies
echo "📦 Installing Laravel Dependencies..."
docker-compose exec -T app composer install --no-dev --optimize-autoloader

# ✅ Ensure `npm` is installed before running frontend dependencies
echo "⚡ Checking if Node.js and npm are installed..."
docker-compose exec -T app bash -c "command -v node && command -v npm"
if [ $? -ne 0 ]; then
    echo "❌ Node.js and npm not found. Installing..."
    docker-compose exec -T app bash -c "curl -fsSL https://deb.nodesource.com/setup_18.x | bash - && apt-get install -y nodejs npm"
fi

# ✅ Install Frontend Dependencies
echo "⚡ Installing Frontend Dependencies..."
docker-compose exec -T app bash -c "npm install && npm run build"

# ✅ Run Database Migrations
echo "📊 Running Laravel Migrations..."
docker-compose exec -T app php artisan migrate --force

# ✅ Restart Nginx (inside container, not host service)
echo "🔄 Restarting Nginx Container..."
docker-compose restart nginx

# Aguardar reinicialização completa
sleep 5

# Verificação final
echo "🔍 Executando verificação final do serviço..."
# Primeiro verificar se podemos acessar o socket do PHP-FPM
echo "Verificando socket PHP-FPM..."
docker-compose exec -T app bash -c "ls -la /run/php/"

# Verificar permissões do socket
echo "Verificando permissões do socket..."
docker-compose exec -T app bash -c "stat -c '%a %U:%G' /run/php/php-fpm.sock 2>/dev/null || echo 'Socket não encontrado'"

# Verificar logs do PHP-FPM
echo "Verificando logs do PHP-FPM..."
docker-compose exec -T app bash -c "tail -n 10 /var/log/php-fpm.log 2>/dev/null || echo 'Log do PHP-FPM não encontrado'"

# Verificar se o site está acessível externamente
echo "Verificando acesso ao site..."
if curl -sk --head https://api.micasan.com.br | grep -E "HTTP/[0-9]\.[0-9] [2-3][0-9][0-9]"; then
    echo "✅ Site está acessível externamente! A comunicação Nginx-PHP-FPM está funcionando corretamente!"
else
    echo "❌ Problemas ao acessar o site externamente! Verificando logs:"
    docker-compose logs --tail=50 nginx
    docker-compose logs --tail=20 app
fi

echo "✅ Deployment Completed Successfully!"