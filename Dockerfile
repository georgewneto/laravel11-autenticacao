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

# Mudar para o usuário www
USER www

# Expor a porta e iniciar o FranklinPHP
EXPOSE 8000
CMD ["/usr/local/bin/frankenphp", "php-server", "--listen", "0.0.0.0:8000", "--root", "/var/www/public"]
