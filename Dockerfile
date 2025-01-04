# Use the official PHP image with FPM
FROM php:8.2.0-fpm

# Copy composer.json to the container
COPY composer.json /var/www/

# Set the working directory
WORKDIR /var/www/

# Install system dependencies
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

# Install PHP extensions
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install pdo_mysql zip exif pcntl gd

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Add user for the application
RUN groupadd -g 1000 www && useradd -u 1000 -ms /bin/bash -g www www

# Copy application files and set permissions
COPY --chown=www:www . /var/www

RUN chown -R www:www /var/www/storage/app/public
RUN chmod -R 777 /var/www/storage/app/public

RUN chmod -R 777 /var/www/storage/logs/laravel.log
RUN chmod -R 777 /var/www/storage/framework/sessions/
RUN chmod -R 777 /var/www/storage/framework/views/

# Change the current user to www
USER www

# Expose the port and start the PHP built-in server
EXPOSE 8000
CMD ["php", "artisan", "serve", "--host=0.0.0.0", "--port=8000"]
