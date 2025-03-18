# Use a imagem oficial do PHP 8.2 com FPM
FROM php:8.2.4-fpm

# Definir diretório de trabalho
WORKDIR /var/www/html

# Instalar dependências do sistema
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
    net-tools \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configurar e instalar extensões do PHP
RUN docker-php-ext-configure gd \
    --with-freetype \
    --with-jpeg \
    --with-webp \
    --with-xpm
RUN docker-php-ext-install pdo pdo_pgsql mbstring exif pcntl bcmath gd zip

# Instalar Composer
COPY --from=composer:2.5.4 /usr/bin/composer /usr/bin/composer

# Copiar arquivos do projeto
COPY . .

# Ajustar permissões
RUN chmod -R 775 storage bootstrap/cache && chown -R www-data:www-data storage bootstrap/cache

# Configurar PHP-FPM para usar Unix socket
RUN sed -i "s|listen = 127.0.0.1:9000|listen = /var/run/php-fpm/php-fpm.sock|g" /usr/local/etc/php-fpm.d/www.conf
RUN echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/www.conf
RUN echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf
RUN echo "listen.mode = 0660" >> /usr/local/etc/php-fpm.d/www.conf

# Criar arquivo .env se não existir
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Gerar chave da aplicação Laravel
RUN php artisan key:generate --force

# Criar arquivos de saúde
RUN echo "OK" > /var/www/html/public/health.txt
RUN echo '<?php phpinfo(); ?>' > /var/www/html/public/info.php

# Configuração do Supervisor para gerenciar processos
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

# Criar diretório do socket Unix do PHP-FPM
RUN mkdir -p /var/run/php-fpm && chown -R www-data:www-data /var/run/php-fpm

# Criar script de inicialização
RUN echo '#!/bin/bash \n\
echo "🚀 Iniciando container no Railway..." \n\
\n\
# Configurar Nginx para escutar na porta do Railway \n\
sed -i "s|listen .*;|listen ${PORT:-9000};|g" /etc/nginx/sites-available/default \n\
nginx -t \n\
\n\
# Iniciar Supervisor para gerenciar Nginx e PHP-FPM \n\
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf' \
> /usr/local/bin/start-container && chmod +x /usr/local/bin/start-container

# Expor porta dinâmica do Railway
EXPOSE 9000

# Iniciar container
CMD ["/usr/local/bin/start-container"]
