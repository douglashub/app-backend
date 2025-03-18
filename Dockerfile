# Use the official PHP 8.2 FPM base image
FROM php:8.2.4-fpm

# Set working directory
WORKDIR /var/www/html

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    zip \
    unzip \
    libpq-dev \
    nginx \
    supervisor \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libwebp-dev \
    libxpm-dev \
    zlib1g-dev \
    libzip-dev \
    dnsutils \
    iputils-ping \
    postgresql-client \
    gettext-base \
    net-tools

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-xpm

RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# Install Composer
COPY --from=composer:2.5.4 /usr/bin/composer /usr/bin/composer

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/deploy

# Copy application files
COPY . .

# Create migration script
RUN echo '#!/bin/bash \n\
MAX_ATTEMPTS=10 \n\
ATTEMPT=0 \n\
\n\
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do \n\
    echo "Attempting to run migrations (Attempt $((ATTEMPT+1))/$MAX_ATTEMPTS)" \n\
    \n\
    php artisan migrate --force \n\
    \n\
    if [ $? -eq 0 ]; then \n\
        echo "Migrations completed successfully!" \n\
        exit 0 \n\
    fi \n\
    \n\
    echo "Migration failed. Waiting 10 seconds before retrying..." \n\
    sleep 10 \n\
    \n\
    ATTEMPT=$((ATTEMPT+1)) \n\
done \n\
\n\
echo "Migrations failed after $MAX_ATTEMPTS attempts" \n\
exit 1' > /var/www/html/deploy/migrate.sh

# Set proper permissions
RUN chmod +x /var/www/html/deploy/migrate.sh \
    && chown -R www-data:www-data \
    /var/www/html/storage \
    /var/www/html/bootstrap/cache \
    /var/www/html/public \
    /var/www/html/deploy

# Install Composer dependencies (production)
RUN composer install --optimize-autoloader --no-dev

# Copy .env.example to .env if it doesn't exist
RUN if [ ! -f .env ]; then \
    cp .env.example .env; \
    fi

# Generate application key if not set
RUN php artisan key:generate --force

# Create simple test HTML file for health checks
RUN echo '<!DOCTYPE html>\n\
<html>\n\
<head>\n\
    <title>Railway Test</title>\n\
</head>\n\
<body>\n\
    <h1>Laravel Application is Running</h1>\n\
    <p>If you can see this page, the server is operational.</p>\n\
</body>\n\
</html>' > /var/www/html/public/test.html

# Modify PHP settings
RUN echo "memory_limit = 256M" > /usr/local/etc/php/conf.d/memory-limit.ini \
    && echo "upload_max_filesize = 20M" > /usr/local/etc/php/conf.d/uploads.ini \
    && echo "post_max_size = 21M" >> /usr/local/etc/php/conf.d/uploads.ini

# Supervisor configuration
RUN echo "[supervisord]\n\
nodaemon=true\n\
\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
\n\
[program:nginx]\n\
command=/usr/sbin/nginx -g 'daemon off;'\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0" > /etc/supervisor/conf.d/supervisord.conf

# Create start script with enhanced diagnostics
RUN echo '#!/bin/bash \n\
# Display environment information \n\
echo "Railway environment information:" \n\
echo "PORT=${PORT:-9000}" \n\
echo "Host: $(hostname)" \n\
\n\
# Configure Nginx to use fixed PORT \n\
echo "Configuring Nginx to listen on port 9000..." \n\
cat > /etc/nginx/sites-available/default << EOF\n\
server { \n\
    listen 9000; \n\
    server_name _; \n\
    root /var/www/html/public; \n\
    index index.php; \n\
    client_max_body_size 100M; \n\
    \n\
    # Ajustes de buffer para evitar 502 Bad Gateway \n\
    fastcgi_buffers 32 32k; \n\
    fastcgi_buffer_size 64k; \n\
    fastcgi_intercept_errors off; \n\
    \n\
    # Aumentar timeouts \n\
    fastcgi_read_timeout 600; \n\
    proxy_read_timeout 600; \n\
    client_body_timeout 600; \n\
    client_header_timeout 600; \n\
    keepalive_timeout 600; \n\
    send_timeout 600; \n\
    \n\
    # Log de depuração \n\
    error_log /dev/stderr; \n\
    access_log /dev/stdout; \n\
    \n\
    # Configuração especial para arquivos estáticos \n\
    location ~* \\.(jpg|jpeg|png|gif|ico|css|js|html)$ { \n\
        expires 30d; \n\
        add_header Cache-Control "public, no-transform"; \n\
        try_files \\$uri =404; \n\
    } \n\
    \n\
    # Permitir acesso ao arquivo info.php \n\
    location = /info.php { \n\
        try_files \\$uri =404; \n\
    } \n\
    \n\
    # Permitir acesso ao arquivo test.html \n\
    location = /test.html { \n\
        try_files \\$uri =404; \n\
    } \n\
    \n\
    location / { \n\
        # Headers CORS \n\
        add_header '\''Access-Control-Allow-Origin'\'' '\''*'\'' always; \n\
        add_header '\''Access-Control-Allow-Methods'\'' '\''GET, POST, OPTIONS, PUT, DELETE'\'' always; \n\
        add_header '\''Access-Control-Allow-Headers'\'' '\''Origin, X-Requested-With, Content-Type, Accept, Authorization'\'' always; \n\
        \n\
        # Handle OPTIONS preflight requests \n\
        if (\\$request_method = '\''OPTIONS'\'') { \n\
            add_header '\''Access-Control-Allow-Origin'\'' '\''*'\''; \n\
            add_header '\''Access-Control-Allow-Methods'\'' '\''GET, POST, OPTIONS, PUT, DELETE'\''; \n\
            add_header '\''Access-Control-Allow-Headers'\'' '\''Origin, X-Requested-With, Content-Type, Accept, Authorization'\''; \n\
            add_header '\''Access-Control-Max-Age'\'' 1728000; \n\
            add_header '\''Content-Type'\'' '\''text/plain; charset=utf-8'\''; \n\
            add_header '\''Content-Length'\'' 0; \n\
            return 204; \n\
        } \n\
        \n\
        try_files \\$uri \\$uri/ /index.php?\\$query_string; \n\
    } \n\
    \n\
    location ~ \\.php$ { \n\
        # Headers CORS para endpoints PHP \n\
        add_header '\''Access-Control-Allow-Origin'\'' '\''*'\'' always; \n\
        add_header '\''Access-Control-Allow-Methods'\'' '\''GET, POST, OPTIONS, PUT, DELETE'\'' always; \n\
        add_header '\''Access-Control-Allow-Headers'\'' '\''Origin, X-Requested-With, Content-Type, Accept, Authorization'\'' always; \n\
        \n\
        fastcgi_pass 127.0.0.1:9001; \n\
        fastcgi_index index.php; \n\
        fastcgi_param SCRIPT_FILENAME \\$document_root\\$fastcgi_script_name; \n\
        include fastcgi_params; \n\
        fastcgi_connect_timeout 600; \n\
        fastcgi_send_timeout 600; \n\
        fastcgi_read_timeout 600; \n\
        \n\
        # Parameters para debug \n\
        fastcgi_param PHP_VALUE "display_errors=On\\ndisplay_startup_errors=On\\nerror_reporting=E_ALL"; \n\
    } \n\
    \n\
    location ~ /\\.(?!well-known).* { \n\
        deny all; \n\
    } \n\
}\n\
EOF\n\
\n\
# Modifica o PHP-FPM para usar a porta 9001 para evitar conflito com o Nginx \n\
echo "Configurando PHP-FPM para usar a porta 9001..." \n\
sed -i "s/listen = 127.0.0.1:9000/listen = 127.0.0.1:9001/g" /usr/local/etc/php-fpm.d/www.conf \n\
\n\
# Configurar timeouts do PHP-FPM \n\
echo "Configurando timeouts do PHP-FPM..." \n\
echo "request_terminate_timeout = 600" >> /usr/local/etc/php-fpm.d/www.conf \n\
echo "max_execution_time = 600" >> /usr/local/etc/php/conf.d/timeouts.ini \n\
\n\
# Test Nginx configuration \n\
echo "Testing Nginx configuration..." \n\
nginx -t \n\
\n\
# Update critical environment variables \n\
echo "Atualizando variáveis de ambiente críticas no .env..." \n\
sed -i "s|APP_URL=.*|APP_URL=https://app-backend-production-b390.up.railway.app|" .env \n\
sed -i "s|SESSION_DOMAIN=.*|SESSION_DOMAIN=app-backend-production-b390.up.railway.app|" .env \n\
sed -i "s|LOG_LEVEL=.*|LOG_LEVEL=debug|" .env \n\
sed -i "s|APP_DEBUG=.*|APP_DEBUG=true|" .env \n\
\n\
# Modify .env file with database connection settings \n\
echo "Updating .env file with database settings from environment variables..." \n\
sed -i "s|DB_HOST=.*|DB_HOST=${DB_HOST:-postgres.railway.internal}|" .env \n\
sed -i "s|DB_PORT=.*|DB_PORT=${DB_PORT:-5432}|" .env \n\
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-railway}|" .env \n\
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-postgres}|" .env \n\
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD:-cZkuwxYNUCrDuaWCccmZwMjvIZiRjyzF}|" .env \n\
\n\
# Tenta conectar ao banco de dados diretamente via PSQL \n\
echo "Tentando conexão direta ao PostgreSQL..." \n\
PGPASSWORD=${DB_PASSWORD:-cZkuwxYNUCrDuaWCccmZwMjvIZiRjyzF} psql -h hopper.proxy.rlwy.net -p 41149 -U postgres -d railway -c "SELECT 1" || echo "Não foi possível conectar ao PostgreSQL" \n\
\n\
# Create a simple PHP info file for diagnosis \n\
echo "<?php phpinfo();" > /var/www/html/public/info.php \n\
echo "Simple PHP info file created at public/info.php" \n\
\n\
# Run migrations with fallback \n\
echo "Running database migrations..." \n\
/var/www/html/deploy/migrate.sh || echo "Proceeding despite migration issues" \n\
\n\
# Create Laravel storage links \n\
echo "Creating storage links..." \n\
php artisan storage:link --force || echo "Storage link creation failed but continuing" \n\
\n\
# Clear caches for diagnostic \n\
echo "Limpando caches para diagnóstico..." \n\
php artisan config:clear \n\
php artisan route:clear \n\
php artisan view:clear \n\
php artisan cache:clear \n\
\n\
# Display important paths and permissions \n\
echo "Verificando permissões e estrutura de diretórios:" \n\
ls -la /var/www/html \n\
ls -la /var/www/html/public \n\
ls -la /var/www/html/storage \n\
\n\
# Check if port 9000 is already in use \n\
echo "Verificando se a porta 9000 já está em uso:" \n\
netstat -tulpn | grep 9000 || echo "Porta 9000 está livre" \n\
\n\
# Start supervisord \n\
echo "Starting supervisord..." \n\
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf \n\
\n\
# Aguardar o início do Nginx e verificar a porta \n\
sleep 5 \n\
echo "Verificando portas em uso:" \n\
netstat -tulpn | grep -E ":9000|:9001"' \
> /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose fixed port 9000
EXPOSE 9000

# Start container
CMD ["/usr/local/bin/start-container"]