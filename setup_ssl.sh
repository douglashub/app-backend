#!/bin/bash

DOMAIN="api.micasan.com.br"
EMAIL="admin@micasan.com.br"

# Instalar Certbot
apt-get update && apt-get install -y certbot python3-certbot-nginx

# Gerar certificado SSL
certbot --nginx --non-interactive --agree-tos -m $EMAIL -d $DOMAIN

# Agendar renovação automática
echo "0 3 * * * certbot renew --quiet" | crontab -
