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

# Create start script with enhanced DB diagnostics
RUN echo '#!/bin/bash \n\
# Display environment information \n\
echo "Railway environment information:" \n\
echo "PORT=${PORT:-8080}" \n\
echo "Host: $(hostname)" \n\
\n\
# Configure Nginx to use dynamic PORT variable \n\
echo "Configuring Nginx to listen on port ${PORT:-8080}..." \n\
cat > /etc/nginx/sites-available/default << EOF\n\
server { \n\
    listen ${PORT:-8080}; \n\
    server_name _; \n\
    root /var/www/html/public; \n\
    index index.php; \n\
    client_max_body_size 100M; \n\
    \n\
    # Aumentar timeouts \n\
    fastcgi_read_timeout 300; \n\
    proxy_read_timeout 300; \n\
    \n\
    # Log de depuração \n\
    error_log /dev/stderr; \n\
    access_log /dev/stdout; \n\
    \n\
    location / { \n\
        try_files \\$uri \\$uri/ /index.php?\\$query_string; \n\
    } \n\
    \n\
    location ~ \\.php$ { \n\
        fastcgi_pass 127.0.0.1:9001; \n\
        fastcgi_index index.php; \n\
        fastcgi_param SCRIPT_FILENAME \\$document_root\\$fastcgi_script_name; \n\
        include fastcgi_params; \n\
        fastcgi_connect_timeout 300; \n\
        fastcgi_send_timeout 300; \n\
        fastcgi_read_timeout 300; \n\
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
# Test Nginx configuration \n\
echo "Testing Nginx configuration..." \n\
nginx -t \n\
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
# Run migrations with fallback \n\
echo "Running database migrations..." \n\
/var/www/html/deploy/migrate.sh || echo "Proceeding despite migration issues" \n\
\n\
# Create Laravel storage links \n\
echo "Creating storage links..." \n\
php artisan storage:link --force || echo "Storage link creation failed but continuing" \n\
\n\
# Cache configuration \n\
echo "Caching configuration..." \n\
php artisan config:clear \n\
php artisan config:cache || echo "Config caching failed but continuing" \n\
php artisan route:clear \n\
php artisan route:cache || echo "Route caching failed but continuing" \n\
php artisan optimize \n\
\n\
# Display important paths and permissions \n\
echo "Verificando permissões e estrutura de diretórios:" \n\
ls -la /var/www/html \n\
ls -la /var/www/html/public \n\
ls -la /var/www/html/storage \n\
\n\
# Start supervisord \n\
echo "Starting supervisord..." \n\
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf \n\
\n\
# Aguardar o início do Nginx e verificar a porta \n\
sleep 5 \n\
echo "Verificando portas em uso:" \n\
netstat -tulpn | grep -E \":${PORT:-8080}|:9001\"' \
> /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose the same port that Railway provides
EXPOSE ${PORT:-8080}

# Start container
CMD ["/usr/local/bin/start-container"]