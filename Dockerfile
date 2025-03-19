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

# Install Node.js and npm (versão 18.x)
RUN curl -fsSL https://deb.nodesource.com/setup_18.x | bash - \
    && apt-get install -y nodejs \
    && npm install -g npm@10

# Configure and install PHP extensions (GD, pdo_pgsql, etc.)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp --with-xpm \
    && docker-php-ext-install gd pdo pdo_pgsql mbstring exif pcntl bcmath zip

# Configure PHP-FPM para escutar via TCP na porta 9000
# (Substitui a configuração padrão que usa Unix socket)
RUN sed -i "s|^listen =.*|listen = 9000|" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s|^;listen.owner.*|listen.owner = www-data|" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s|^;listen.group.*|listen.group = www-data|" /usr/local/etc/php-fpm.d/www.conf \
    && sed -i "s|^;listen.mode.*|listen.mode = 0660|" /usr/local/etc/php-fpm.d/www.conf

# Install Composer (copiado da imagem oficial composer)
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

# Set environment variable to allow Composer to run as root
ENV COMPOSER_ALLOW_SUPERUSER=1

# Copy all application files
COPY . .

# Create an .env file if it doesn't exist
RUN test -f .env || cp .env.example .env

# Install Composer dependencies with proper permissions
RUN composer install --optimize-autoloader --no-dev --no-scripts \
    && php artisan key:generate --force \
    && chown -R www-data:www-data /var/www/html/vendor

# Ensure migrate.sh has execute permissions, if it exists
RUN if [ -f /var/www/html/deploy/migrate.sh ]; then \
    chmod +x /var/www/html/deploy/migrate.sh; \
    chown www-data:www-data /var/www/html/deploy/migrate.sh; \
fi

# Supervisor configuration (opcional – se quiser usar Supervisor para gerenciar processos no contêiner)
RUN echo "[supervisord]\n\
nodaemon=true\n\
user=root\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm --nodaemonize\n\
autostart=true\n\
autorestart=true\n\
stdout_logfile=/dev/stdout\n\
stdout_logfile_maxbytes=0\n\
stderr_logfile=/dev/stderr\n\
stderr_logfile_maxbytes=0" > /etc/supervisor/conf.d/supervisord.conf

# Script de inicialização do container (start-container)
RUN echo '#!/bin/bash\n\
# Executar as migrations do Laravel se houver\n\
if [ -x /var/www/html/deploy/migrate.sh ]; then\n\
    echo "Executando script de migração..."\n\
    bash /var/www/html/deploy/migrate.sh\n\
else\n\
    echo "Rodando Laravel migrations..."\n\
    cd /var/www/html && php artisan migrate --force\n\
fi\n\
\n\
# Iniciar o PHP-FPM em primeiro plano\n\
exec php-fpm --nodaemonize' > /usr/local/bin/start-container \
    && chmod +x /usr/local/bin/start-container

# Expose porta 9000 para PHP-FPM (usada internamente)
EXPOSE 9000

# Start the container
CMD ["/usr/local/bin/start-container"]
