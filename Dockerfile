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
    && mkdir -p /var/www/html/deploy \
    && mkdir -p /etc/nginx/templates

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

# Create Nginx configuration template
RUN echo 'server { \
    listen ${PORT:-80}; \
    server_name _; \
    root /var/www/html/public; \
    index index.php; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        fastcgi_pass 127.0.0.1:9000; \
        fastcgi_index index.php; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        include fastcgi_params; \
    } \
}' > /etc/nginx/templates/default.conf.template

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

# Create a healthcheck script
RUN echo '#!/bin/sh \n\
wget -q -O - http://localhost:${PORT:-80}/api/test || exit 1' > /usr/local/bin/healthcheck \
    && chmod +x /usr/local/bin/healthcheck

# Create start script with expanded debugging
RUN echo '#!/bin/bash \n\
# Display environment information \n\
echo "Railway environment information:" \n\
echo "PORT=${PORT:-80}" \n\
echo "Host: $(hostname)" \n\
echo \n\
\n\
# Process Nginx template with PORT variable \n\
echo "Configuring Nginx to listen on port ${PORT:-80}..." \n\
envsubst "\$PORT" < /etc/nginx/templates/default.conf.template > /etc/nginx/sites-available/default \n\
cat /etc/nginx/sites-available/default \n\
\n\
# Test Nginx configuration \n\
echo "Testing Nginx configuration..." \n\
nginx -t \n\
\n\
# Test database connection \n\
echo "Testing database connection..." \n\
php -r "try { \n\
    \$dbconn = new PDO( \n\
        \"pgsql:host=${DB_HOST:-postgres.railway.internal};port=${DB_PORT:-5432};dbname=${DB_DATABASE:-railway}\", \n\
        \"${DB_USERNAME:-postgres}\", \n\
        \"${DB_PASSWORD}\" \n\
    ); \n\
    echo \"Database connection successful\\n\"; \n\
} catch (\\PDOException \$e) { \n\
    echo \"Database connection failed: \" . \$e->getMessage() . \"\\n\"; \n\
}" \n\
\n\
# Run migrations \n\
echo "Running database migrations..." \n\
/var/www/html/deploy/migrate.sh \n\
\n\
# Create Laravel storage links \n\
echo "Creating storage links..." \n\
php artisan storage:link --force \n\
\n\
# Cache configuration \n\
echo "Caching configuration..." \n\
php artisan config:cache \n\
php artisan route:cache \n\
\n\
# Start supervisord \n\
echo "Starting supervisord..." \n\
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' \
> /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose port based on PORT environment variable or default to 80
EXPOSE ${PORT:-80}

# Add healthcheck
HEALTHCHECK --interval=30s --timeout=5s --start-period=30s --retries=3 CMD /usr/local/bin/healthcheck

# Start container
CMD ["/usr/local/bin/start-container"]