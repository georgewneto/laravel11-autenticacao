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
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Instalar extensões do PHP
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql zip exif pcntl gd

# Instalar Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Instalar FrankenPHP
RUN curl -sSL https://github.com/dunglas/frankenphp/releases/latest/download/frankenphp-linux-x86_64 -o /usr/local/bin/frankenphp \
    && chmod +x /usr/local/bin/frankenphp

# Adicionar usuário para a aplicação
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Copiar arquivos da aplicação e definir permissões
COPY --chown=www:www . /var/www

RUN chown -R www:www /var/www/storage/app/public
RUN chmod -R 777 /var/www/storage/app/public

RUN chmod -R 777 /var/www/storage/logs/laravel.log
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

# Mudar para o usuário www
USER www

# Expose both HTTP and HTTPS ports
EXPOSE 8008 8443

# Start Laravel with HTTPS support using the artisan serve command
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8008"]
