# Use a imagem oficial do PHP com FPM
FROM php:8.2.0-fpm

# Copiar composer.json para o container
COPY composer.json /var/www/

# Definir o diretório de trabalho
WORKDIR /var/www/

# Instalar dependências do sistema
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim \
    optipng \
    pngquant \
    gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    zlib1g-dev \
    certbot \
    openssl \
    ca-certificates \
    nginx \
    supervisor \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensões do PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql zip exif pcntl gd

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Adicionar usuário para a aplicação
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Copiar arquivos da aplicação e definir permissões
COPY --chown=www:www . /var/www

RUN chown -R www:www /var/www/storage/app/public
RUN chmod -R 777 /var/www/storage/app/public

#RUN chmod -R 777 /var/www/storage/logs/laravel.log
RUN chmod -R 777 /var/www/storage/framework/sessions/
RUN chmod -R 777 /var/www/storage/framework/views/

# Create SSL directory
RUN mkdir -p /var/www/ssl

# Use Let's Encrypt for development with mkcert (more browser-friendly)
RUN apt-get update && apt-get install -y libnss3-tools wget && \
    wget -O /usr/local/bin/mkcert https://github.com/FiloSottile/mkcert/releases/download/v1.4.3/mkcert-v1.4.3-linux-amd64 && \
    chmod +x /usr/local/bin/mkcert && \
    mkdir -p /root/.local/share/mkcert

# Set permission for the SSL directory
RUN chown -R www:www /var/www/ssl

# Add script to generate certificates
COPY --chown=www:www ./docker/generate-cert.sh /var/www/generate-cert.sh
RUN chmod +x /var/www/generate-cert.sh

# Copy Nginx configuration
COPY ./docker/nginx.conf /etc/nginx/sites-available/default

# Create supervisor configuration for Nginx and PHP-FPM
RUN echo '[supervisord]' > /etc/supervisor/conf.d/supervisord.conf && \
    echo 'nodaemon=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:php-fpm]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=/usr/local/sbin/php-fpm' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/dev/stdout' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/dev/stderr' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo '[program:nginx]' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'command=/usr/sbin/nginx -g "daemon off;"' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autostart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'autorestart=true' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile=/dev/stdout' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stdout_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile=/dev/stderr' >> /etc/supervisor/conf.d/supervisord.conf && \
    echo 'stderr_logfile_maxbytes=0' >> /etc/supervisor/conf.d/supervisord.conf

# Expose HTTP and HTTPS ports
EXPOSE 80 443

# Start services with supervisor
CMD ["/bin/bash", "-c", "/var/www/generate-cert.sh ${APP_DOMAIN:-localhost} && /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf"]
