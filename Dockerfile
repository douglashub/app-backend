# Use PHP 8.2 FPM base image
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
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install gd pdo pdo_pgsql mbstring exif pcntl bcmath zip

# Install Composer
COPY --from=composer:2.5.4 /usr/bin/composer /usr/bin/composer

# Create necessary directories with proper permissions
RUN mkdir -p /var/www/html/storage/framework/sessions \
    /var/www/html/storage/framework/views \
    /var/www/html/storage/framework/cache \
    /var/www/html/bootstrap/cache \
    /var/www/html/deploy \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html

# Copy application files
COPY . .

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev

# Generate application key (after ensuring `.env` exists)
RUN cp .env.example .env && php artisan key:generate --force

# Migration script with database connection check
RUN echo '#!/bin/bash \n\
MAX_ATTEMPTS=10 \n\
ATTEMPT=0 \n\
DB_HOST="db-postgres-api-micasan-do-user-20111967-0.f.db.ondigitalocean.com" \n\
DB_PORT="25060" \n\
while [ $ATTEMPT -lt $MAX_ATTEMPTS ]; do \n\
    echo "Checking PostgreSQL connection (Attempt $((ATTEMPT+1))/$MAX_ATTEMPTS)" \n\
    nc -zv $DB_HOST $DB_PORT && break \n\
    sleep 5 \n\
    ATTEMPT=$((ATTEMPT+1)) \n\
done \n\
echo "Running migrations..." \n\
php artisan migrate --force' > /var/www/html/deploy/migrate.sh \
    && chmod +x /var/www/html/deploy/migrate.sh

# Nginx Configuration (inside container)
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /var/www/html/public; \
    index index.php index.html; \
    location / { \
        try_files $uri $uri/ /index.php?$query_string; \
    } \
    location ~ \.php$ { \
        include fastcgi_params; \
        fastcgi_pass unix:/run/php/php-fpm.sock; \
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name; \
        fastcgi_index index.php; \
    } \
}' > /etc/nginx/sites-available/default

# Supervisor configuration
RUN echo "[supervisord]\n\
nodaemon=true\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stderr_logfile=/dev/stderr\n\
[program:nginx]\n\
command=/usr/sbin/nginx -g 'daemon off;'\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stderr_logfile=/dev/stderr" > /etc/supervisor/conf.d/supervisord.conf

# Create start script
RUN echo '#!/bin/bash \n\
# Ensure PostgreSQL is ready \n\
/var/www/html/deploy/migrate.sh \n\
# Start Supervisor \n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose port 80
EXPOSE 80

# Start the container
CMD ["/usr/local/bin/start-container"]
