# Usa a versão correta do PHP com FPM
FROM php:8.2.4-fpm

# Define o diretório de trabalho
WORKDIR /var/www/html

# Instala dependências do sistema
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

# Configura e instala extensões PHP
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-xpm \
    && docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# Instala o Composer
COPY --from=composer:2.5.4 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do Laravel
COPY . .

# ✅ Criação das pastas necessárias para o Laravel
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/bootstrap/cache \
    && chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache \
    && chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache

# ✅ Instala as dependências do Laravel
RUN COMPOSER_ALLOW_SUPERUSER=1 composer install --no-dev --optimize-autoloader

# ✅ Gera a chave da aplicação Laravel
RUN php artisan key:generate --force

# Configuração do Supervisor para gerenciar processos
RUN printf "[supervisord]\n\
[program:php-fpm]\n\
command=/usr/local/sbin/php-fpm\n\
autostart=true\n\
autorestart=true\n\
\n\
[program:nginx]\n\
command=nginx -g 'daemon off;'\n\
autostart=true\n\
autorestart=true\n" > /etc/supervisor/conf.d/supervisord.conf

# Expor portas
EXPOSE 80 443

# Inicia o supervisor para gerenciar processos do PHP e Nginx
CMD ["/usr/bin/supervisord", "-c", "/etc/supervisor/conf.d/supervisord.conf"]
