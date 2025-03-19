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

# Copy composer files first for better cache utilization
COPY composer.json composer.lock ./

# Install Composer dependencies
RUN composer install --optimize-autoloader --no-dev

# Copy application files (after dependencies are installed)
COPY . .

# Generate application key (after ensuring `.env` exists)
RUN cp .env.example .env && php artisan key:generate --force

# Copy migration script instead of creating it inline
COPY deploy/migrate.sh /var/www/html/deploy/migrate.sh

# Ensure migrate.sh has execute permissions for all users
RUN chmod +x /var/www/html/deploy/migrate.sh \
    && chmod 755 /var/www/html/deploy/migrate.sh \
    && chown www-data:www-data /var/www/html/deploy/migrate.sh

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
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0\n\
[program:nginx]\n\
command=/usr/sbin/nginx -g 'daemon off;'\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0" > /etc/supervisor/conf.d/supervisord.conf

# Create start script with inline migrate script as fallback
RUN echo '#!/bin/bash\n\
# Check if vendor directory exists
if [ ! -d /var/www/html/vendor ]; then\n\
    echo "Vendor directory missing. Running composer install..."\n\
    composer install --no-dev --optimize-autoloader\n\
fi\n\
# Try to run the migration script\n\
if [ -x /var/www/html/deploy/migrate.sh ]; then\n\
    echo "Running migration script..."\n\
    bash /var/www/html/deploy/migrate.sh\n\
else\n\
    echo "Migration script not executable, running inline migration..."\n\
    php /var/www/html/artisan migrate --force\n\
fi\n\
# Start Supervisor\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose port 80
EXPOSE 80

# Start the container
CMD ["/usr/local/bin/start-container"]