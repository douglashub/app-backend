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
    certbot \
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
    /var/www/letsencrypt \
    /certbot-www \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache /var/www/letsencrypt /certbot-www \
    && chown -R www-data:www-data /var/www/html /var/www/letsencrypt /certbot-www

# Set environment variable to allow Composer to run as root/superuser
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy all application files
COPY . .

# Create an .env file if it doesn't exist
RUN test -f .env || cp .env.example .env

# Install Composer dependencies with proper permissions
RUN composer install --optimize-autoloader --no-dev --no-scripts \
    && php artisan key:generate --force \
    && chown -R www-data:www-data /var/www/html/vendor

# Ensure migrate.sh has execute permissions
RUN if [ -f /var/www/html/deploy/migrate.sh ]; then \
    chmod +x /var/www/html/deploy/migrate.sh; \
    chown www-data:www-data /var/www/html/deploy/migrate.sh; \
fi

# Configure PHP-FPM
RUN echo 'listen = /run/php/php-fpm.sock' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'listen.owner = www-data' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'listen.group = www-data' >> /usr/local/etc/php-fpm.d/www.conf \
    && echo 'listen.mode = 0660' >> /usr/local/etc/php-fpm.d/www.conf

# Nginx Configuration
RUN echo 'server { \
    listen 80; \
    server_name _; \
    root /var/www/html/public; \
    index index.php index.html; \
    error_log  /var/log/nginx/error.log; \
    access_log /var/log/nginx/access.log; \
    client_max_body_size 100M; \
    location /.well-known/acme-challenge/ { \
        root /certbot-www; \
        allow all; \
    } \
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

# Make sure the sock directory exists
RUN mkdir -p /run/php

# Supervisor configuration
RUN echo "[supervisord]\n\
nodaemon=true\n\
user=root\n\
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

# Certbot Auto-renewal script
RUN echo '#!/bin/bash\n\
while :; do\n\
  certbot renew --webroot -w /certbot-www --quiet\n\
  sleep 12h\n\
done' > /usr/local/bin/certbot-auto-renew && chmod +x /usr/local/bin/certbot-auto-renew

# Criar o script de inicialização do container
RUN echo '#!/bin/bash\n\
# Garantir que os diretórios existam e tenham as permissões corretas\n\
mkdir -p /var/www/html/storage/framework/sessions\n\
mkdir -p /var/www/html/storage/framework/views\n\
mkdir -p /var/www/html/storage/framework/cache\n\
mkdir -p /var/www/html/bootstrap/cache\n\
chown -R www-data:www-data /var/www/html/storage\n\
chown -R www-data:www-data /var/www/html/bootstrap/cache\n\
\n\
# Executar as migrations do Laravel\n\
if [ -x /var/www/html/deploy/migrate.sh ]; then\n\
    echo "Executando script de migração..."\n\
    bash /var/www/html/deploy/migrate.sh\n\
else\n\
    echo "Rodando Laravel migrations..."\n\
    cd /var/www/html && php artisan migrate --force\n\
fi\n\
\n\
# Iniciar Supervisor (para gerenciar PHP-FPM e Nginx)\n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' > /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose ports
EXPOSE 80 443

# Start the container
CMD ["/usr/local/bin/start-container"]
