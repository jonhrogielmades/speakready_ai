FROM php:8.2-fpm

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    nodejs \
    npm \
    dos2unix \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Get latest Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy composer files first to leverage Docker cache
COPY composer.json composer.lock ./

# Fix any Windows line ending issues safely
RUN dos2unix composer.json composer.lock || true

# Install dependencies without memory limits and ignore platform reqs (failsafe)
ENV COMPOSER_ALLOW_SUPERUSER=1
RUN php -d memory_limit=-1 /usr/bin/composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts --ignore-platform-reqs

# Send Laravel logs to container stderr by default so Render can collect them
ENV LOG_CHANNEL=stderr
ENV LOG_EMERGENCY_PATH=php://stderr

# Copy existing application directory contents
COPY . /var/www

# Copy Nginx config
COPY nginx.conf /etc/nginx/sites-enabled/default

# Set Laravel writable directory permissions
RUN mkdir -p \
    /var/www/storage/app/public \
    /var/www/storage/framework/cache/data \
    /var/www/storage/framework/sessions \
    /var/www/storage/framework/views \
    /var/www/storage/logs \
    /var/www/bootstrap/cache \
    && touch /var/www/storage/logs/laravel.log \
    && chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R ug+rwX /var/www/storage /var/www/bootstrap/cache

# Expose port (Render sets PORT environment variable, usually 80 or 10000. Nginx will listen on 80)
EXPOSE 80

# Make the start script executable and fix line endings
RUN dos2unix /var/www/render-start.sh && chmod +x /var/www/render-start.sh

# Start Nginx and PHP-FPM
CMD ["/var/www/render-start.sh"]
