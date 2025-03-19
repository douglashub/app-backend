services:
    app:
      build:
        context: .
        dockerfile: Dockerfile
      container_name: laravel_app
      restart: unless-stopped
      working_dir: /var/www/html
      volumes:
        - .:/var/www/html
        - /var/www/html/vendor
        - ./storage:/var/www/html/storage
        - ./bootstrap/cache:/var/www/html/bootstrap/cache
      env_file:
        - .env
      healthcheck:
        test: ["CMD", "php", "-v"]
        interval: 10s
        timeout: 5s
        retries: 3
        start_period: 5s
      networks:
        - laravel_network
  
    nginx:
      image: nginx:alpine
      container_name: nginx_server
      restart: unless-stopped
      ports:
        - "80:80"
        - "443:443"
      volumes:
        - .:/var/www/html
        - /var/www/html/vendor
        - ./docker/nginx/default.conf:/etc/nginx/conf.d/default.conf
        - /etc/letsencrypt:/etc/letsencrypt  # ✅ Mount SSL certs into the container
      depends_on:
        - app
      networks:
        - laravel_network
  
  networks:
    laravel_network:
      driver: bridge
  