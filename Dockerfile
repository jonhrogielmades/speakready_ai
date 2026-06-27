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

# Copy existing application directory contents
COPY . /var/www

# Install dependencies without memory limits
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_MEMORY_LIMIT=-1
RUN dos2unix composer.json composer.lock && \
    php -d memory_limit=-1 /usr/bin/composer install --no-interaction --prefer-dist --optimize-autoloader --no-dev --no-scripts --no-progress -v
# Copy Nginx config
COPY nginx.conf /etc/nginx/sites-enabled/default

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Expose port (Render sets PORT environment variable, usually 80 or 10000. Nginx will listen on 80)
EXPOSE 80

# Make the start script executable and fix line endings
RUN dos2unix /var/www/render-start.sh && chmod +x /var/www/render-start.sh

# Start Nginx and PHP-FPM
CMD ["/var/www/render-start.sh"]
