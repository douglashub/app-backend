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
    dnsutils

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure and install PHP extensions
RUN docker-php-ext-configure gd \
        --with-freetype \
        --with-jpeg \
        --with-webp \
        --with-xpm

# Install PHP extensions
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

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy .env.example to .env
COPY .env.example .env

# Generate application key
RUN php artisan key:generate --force

# Create nginx configuration
RUN echo 'server { \
    listen 0.0.0.0:$PORT; \
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
}' > /etc/nginx/sites-available/default

# Create supervisord configuration
RUN echo '[supervisord] \
nodaemon=true \
\n\
[program:php-fpm] \
command=/usr/local/sbin/php-fpm \
autostart=true \
autorestart=true \
\n\
[program:nginx] \
command=/usr/sbin/nginx -g "daemon off;" \
autostart=true \
autorestart=true' > /etc/supervisor/conf.d/supervisord.conf

# DNS resolution check
RUN echo "Resolving postgres.railway.internal..." \
    && nslookup postgres.railway.internal || (echo "DNS resolution failed" && exit 1)

# Create start script
RUN echo '#!/bin/bash \n\
# Run migrations \n\
/var/www/html/deploy/migrate.sh \n\
\n\
# Start supervisord \n\
/usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Display the contents of .env and .env.example after successful build
RUN echo "Contents of .env:" \
    && cat .env \
    && echo "\nContents of .env.example:" \
    && cat .env.example

# Expose port
EXPOSE $PORT

# Start container
CMD ["/usr/local/bin/start-container"]