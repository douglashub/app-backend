# Usa a imagem base oficial do PHP com FPM
FROM php:8.2-fpm

# Define o diretório de trabalho
WORKDIR /var/www

# Instala dependências do sistema
RUN apt-get update && apt-get install -y \
    curl \
    git \
    unzip \
    zip \
    libpq-dev \
    nginx \
    supervisor \
    certbot \
    python3-certbot-nginx \
    && docker-php-ext-install pdo pdo_pgsql bcmath

# Instala o Composer
COPY --from=composer:2.5.4 /usr/bin/composer /usr/bin/composer

# Copia os arquivos do Laravel
COPY . .

# Ajusta permissões
RUN chmod -R 775 storage bootstrap/cache \
    && chown -R www-data:www-data storage bootstrap/cache

# Gera a chave da aplicação Laravel
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
