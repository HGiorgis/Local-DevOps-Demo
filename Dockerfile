FROM php:8.4-fpm-alpine

# Install system dependencies including build tools for PECL
RUN apk update && apk add --no-cache \
    bash \
    nginx \
    curl \
    git \
    zip \
    unzip \
    supervisor \
    libpng-dev \
    libjpeg-turbo-dev \
    freetype-dev \
    libzip-dev \
    oniguruma-dev \
    linux-headers \
    $PHPIZE_DEPS

# Install PHP extensions for PHP 8.4
RUN docker-php-ext-configure gd --with-freetype --with-jpeg
RUN docker-php-ext-install \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip

# Install Redis extension for PHP 8.4
RUN pecl channel-update pecl.php.net && \
    pecl install redis && \
    docker-php-ext-enable redis

# Clean up build dependencies to keep image small
RUN apk del $PHPIZE_DEPS && \
    rm -rf /tmp/pear

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Configure PHP for Laravel
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory.ini && \
    echo "upload_max_filesize = 100M" > /usr/local/etc/php/conf.d/upload.ini && \
    echo "post_max_size = 100M" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/upload.ini && \
    echo "opcache.enable=1" >> /usr/local/etc/php/conf.d/opcache.ini && \
    echo "opcache.memory_consumption=256" >> /usr/local/etc/php/conf.d/opcache.ini

# Configure Nginx
RUN mkdir -p /var/www/html && \
    mkdir -p /run/nginx

COPY nginx/nginx-app.conf /etc/nginx/http.d/default.conf

# Set working directory
WORKDIR /var/www/html

# Change ownership
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html

# Expose port
EXPOSE 80

# Start PHP-FPM and Nginx
CMD ["sh", "-c", "php-fpm & nginx -g 'daemon off;'"]