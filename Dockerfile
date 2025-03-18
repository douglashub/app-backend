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
    gettext-base

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

# Database testing script
RUN echo '#!/bin/bash\n\
# Try to connect to PostgreSQL with provided credentials\n\
PGPASSWORD=${PGPASSWORD} psql -h ${PGHOST:-postgres.railway.internal} -p ${PGPORT:-5432} -U ${PGUSER:-postgres} -d ${PGDATABASE:-railway} -c "SELECT 1;"' > /usr/local/bin/test-db.sh \
    && chmod +x /usr/local/bin/test-db.sh

# Create start script with enhanced DB diagnostics
RUN echo '#!/bin/bash \n\
# Display environment information \n\
echo "Railway environment information:" \n\
echo "PORT=${PORT:-8080}" \n\
echo "Host: $(hostname)" \n\
\n\
# Configure Nginx hardcoding the port \n\
echo "Configuring Nginx to listen on port ${PORT:-8080}..." \n\
cat > /etc/nginx/sites-available/default << EOF\n\
server { \n\
    listen ${PORT:-8080}; \n\
    server_name _; \n\
    root /var/www/html/public; \n\
    index index.php; \n\
    \n\
    location / { \n\
        try_files \\$uri \\$uri/ /index.php?\\$query_string; \n\
    } \n\
    \n\
    location ~ \\.php$ { \n\
        fastcgi_pass 127.0.0.1:9000; \n\
        fastcgi_index index.php; \n\
        fastcgi_param SCRIPT_FILENAME \\$document_root\\$fastcgi_script_name; \n\
        include fastcgi_params; \n\
    } \n\
}\n\
EOF\n\
\n\
# Test Nginx configuration \n\
echo "Testing Nginx configuration..." \n\
nginx -t \n\
\n\
# Database diagnostic tests \n\
echo "Performing database connectivity diagnostics..." \n\
echo "Host resolution test:" \n\
getent hosts postgres.railway.internal || echo "Cannot resolve postgres.railway.internal" \n\
getent hosts hopper.proxy.rlwy.net || echo "Cannot resolve hopper.proxy.rlwy.net" \n\
getent hosts postgres || echo "Cannot resolve postgres" \n\
\n\
# Try direct connection to Postgres \n\
echo "Testing PostgreSQL connection..." \n\
/usr/local/bin/test-db.sh || echo "PostgreSQL connection test failed" \n\
\n\
# Modify .env file with database connection settings \n\
echo "Updating .env file with database settings from environment variables..." \n\
sed -i "s/DB_HOST=.*/DB_HOST=${PGHOST:-postgres.railway.internal}/" .env \n\
sed -i "s/DB_PORT=.*/DB_PORT=${PGPORT:-5432}/" .env \n\
sed -i "s/DB_DATABASE=.*/DB_DATABASE=${PGDATABASE:-railway}/" .env \n\
sed -i "s/DB_USERNAME=.*/DB_USERNAME=${PGUSER:-postgres}/" .env \n\
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${PGPASSWORD:-cZkuwxYNUCrDuaWCccmZwMjvIZiRjyzF}|" .env \n\
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
php artisan config:cache || echo "Config caching failed but continuing" \n\
php artisan route:cache || echo "Route caching failed but continuing" \n\
\n\
# Start supervisord \n\
echo "Starting supervisord..." \n\
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' \
> /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose port
EXPOSE 8080

# Start container
CMD ["/usr/local/bin/start-container"]