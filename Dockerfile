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

# Criar diretórios necessários para o Laravel
RUN mkdir -p /var/www/html/storage/framework/sessions \
    && mkdir -p /var/www/html/storage/framework/views \
    && mkdir -p /var/www/html/storage/framework/cache \
    && mkdir -p /var/www/html/bootstrap/cache \
    && mkdir -p /var/www/html/public

# Copiar arquivos do projeto
COPY . .

# Ajustar permissões
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache public

# Criar arquivo de configuração Nginx usando cat em vez de echo
RUN cat > /etc/nginx/sites-available/default << 'EOL'
server {
    listen ${PORT:-9000};
    server_name _;

    root /var/www/html/public;
    index index.php index.html;

    client_max_body_size 100M;

    # Ajustes de buffer para evitar 502 Bad Gateway
    fastcgi_buffers 32 32k;
    fastcgi_buffer_size 64k;
    fastcgi_intercept_errors off;

    # Aumentar timeouts
    fastcgi_read_timeout 600;
    proxy_read_timeout 600;
    client_body_timeout 600;
    client_header_timeout 600;
    keepalive_timeout 600;
    send_timeout 600;

    # Logs
    error_log /dev/stderr;
    access_log /dev/stdout;

    # Configuração especial para arquivos estáticos
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|html|txt)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
        try_files $uri =404;
    }

    # Permitir acesso ao arquivo info.php
    location = /info.php {
        try_files $uri =404;
    }

    # Permitir acesso ao arquivo test.html
    location = /test.html {
        try_files $uri =404;
    }

    # Adicionar arquivo de health check
    location = /health.txt {
        default_type text/plain;
        return 200 "OK";
    }

    location / {
        # Headers CORS
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS, PUT, DELETE' always;
        add_header 'Access-Control-Allow-Headers' 'Origin, X-Requested-With, Content-Type, Accept, Authorization' always;

        # Handle OPTIONS preflight requests
        if ($request_method = 'OPTIONS') {
            add_header 'Access-Control-Allow-Origin' '*';
            add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS, PUT, DELETE';
            add_header 'Access-Control-Allow-Headers' 'Origin, X-Requested-With, Content-Type, Accept, Authorization';
            add_header 'Access-Control-Max-Age' 1728000;
            add_header 'Content-Type' 'text/plain; charset=utf-8';
            add_header 'Content-Length' 0;
            return 204;
        }

        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        # Headers CORS para endpoints PHP
        add_header 'Access-Control-Allow-Origin' '*' always;
        add_header 'Access-Control-Allow-Methods' 'GET, POST, OPTIONS, PUT, DELETE' always;
        add_header 'Access-Control-Allow-Headers' 'Origin, X-Requested-With, Content-Type, Accept, Authorization' always;

        # Usando socket Unix em vez de TCP
        fastcgi_pass unix:/var/run/php-fpm/php-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_connect_timeout 600;
        fastcgi_send_timeout 600;
        fastcgi_read_timeout 600;

        # Parameters para debug
        fastcgi_param PHP_VALUE "display_errors=On\ndisplay_startup_errors=On\nerror_reporting=E_ALL";
    }

    # Bloquear acesso a arquivos ocultos, como .env
    location ~ /\.(?!well-known).* {
        deny all;
    }
}
EOL

# Configurar PHP-FPM para usar Unix socket
RUN sed -i "s|listen = 127.0.0.1:9000|listen = /var/run/php-fpm/php-fpm.sock|g" /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.owner = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.group = www-data" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "listen.mode = 0660" >> /usr/local/etc/php-fpm.d/www.conf \
    && echo "request_terminate_timeout = 600" >> /usr/local/etc/php-fpm.d/www.conf

# Criar arquivo .env se não existir
RUN if [ ! -f .env ]; then cp .env.example .env; fi

# Instalar dependências do Composer e otimizar
RUN composer install --optimize-autoloader --no-dev

# Gerar chave da aplicação Laravel
RUN php artisan key:generate --force

# Criar arquivos de saúde
RUN echo "OK" > /var/www/html/public/health.txt \
    && echo '<?php phpinfo();' > /var/www/html/public/info.php \
    && echo '<!DOCTYPE html><html><head><title>Teste</title></head><body><h1>Aplicação Laravel Rodando!</h1></body></html>' > /var/www/html/public/test.html

# Configuração do Supervisor
RUN cat > /etc/supervisor/conf.d/supervisord.conf << 'EOL'
[supervisord]
nodaemon=true

[program:php-fpm]
command=/usr/local/sbin/php-fpm
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0

[program:nginx]
command=/usr/sbin/nginx -g 'daemon off;'
autostart=true
autorestart=true
stdout_logfile=/dev/stdout
stdout_logfile_maxbytes=0
stderr_logfile=/dev/stderr
stderr_logfile_maxbytes=0
EOL

# Criar diretório do socket Unix do PHP-FPM
RUN mkdir -p /var/run/php-fpm && chown -R www-data:www-data /var/run/php-fpm

# Criar script de inicialização
RUN cat > /usr/local/bin/start-container << 'EOL'
#!/bin/bash
echo "🚀 Iniciando container no Railway..."

# Configurar a porta do Nginx dinamicamente
sed -i "s|listen .*;|listen ${PORT:-9000};|g" /etc/nginx/sites-available/default

# Atualizar variáveis de ambiente críticas
sed -i "s|APP_URL=.*|APP_URL=https://app-backend-production-b390.up.railway.app|" .env
sed -i "s|SESSION_DOMAIN=.*|SESSION_DOMAIN=app-backend-production-b390.up.railway.app|" .env
sed -i "s|LOG_LEVEL=.*|LOG_LEVEL=debug|" .env
sed -i "s|APP_DEBUG=.*|APP_DEBUG=true|" .env

# Atualizar configurações de banco de dados
sed -i "s|DB_HOST=.*|DB_HOST=${DB_HOST:-postgres.railway.internal}|" .env
sed -i "s|DB_PORT=.*|DB_PORT=${DB_PORT:-5432}|" .env
sed -i "s|DB_DATABASE=.*|DB_DATABASE=${DB_DATABASE:-railway}|" .env
sed -i "s|DB_USERNAME=.*|DB_USERNAME=${DB_USERNAME:-postgres}|" .env
sed -i "s|DB_PASSWORD=.*|DB_PASSWORD=${DB_PASSWORD}|" .env

# Verificar configuração do Nginx
echo "Testando configuração do Nginx..."
nginx -t

# Limpar caches Laravel
php artisan config:clear
php artisan route:clear
php artisan view:clear
php artisan cache:clear

# Criar diretório para socket se não existir
mkdir -p /var/run/php-fpm
chown -R www-data:www-data /var/run/php-fpm

echo "Iniciando supervisord..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
EOL

# Tornar o script executável
RUN chmod +x /usr/local/bin/start-container

# Expor porta dinâmica do Railway
EXPOSE 9000

# Iniciar container
CMD ["/usr/local/bin/start-container"]