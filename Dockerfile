FROM php:8.2-fpm

ARG NODE_MAJOR=22
ENV PORT=10000

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    ca-certificates \
    libpng-dev \
    libonig-dev \
    libxml2-dev \
    libpq-dev \
    libzip-dev \
    zip \
    unzip \
    nginx \
    dos2unix \
    && curl -fsSL https://deb.nodesource.com/setup_${NODE_MAJOR}.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql pdo_pgsql mbstring exif pcntl bcmath gd zip

# Allow long-running admin generation requests without PHP-FPM ending the worker first.
RUN { \
        echo "max_execution_time=3600"; \
        echo "max_input_time=3600"; \
        echo "memory_limit=512M"; \
    } > /usr/local/etc/php/conf.d/99-render-timeouts.ini \
    && { \
        echo "[www]"; \
        echo "request_terminate_timeout = 3600s"; \
    } > /usr/local/etc/php-fpm.d/zz-render-timeouts.conf

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

# Install frontend dependencies separately so Docker can cache them between app code changes.
COPY package.json package-lock.json ./
RUN npm ci

# Send Laravel logs to container stderr by default so Render can collect them
ENV LOG_CHANNEL=stderr
ENV LOG_EMERGENCY_PATH=php://stderr

# Copy existing application directory contents
COPY . /var/www

# Build production frontend assets inside the image. public/build is intentionally
# ignored by Git/Docker context, so Render needs this step during the Docker build.
RUN npm run build && rm -rf node_modules

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

# Render web services expect the app to bind to $PORT.
EXPOSE 10000

# Make the start script executable and fix line endings
RUN dos2unix /var/www/render-start.sh && chmod +x /var/www/render-start.sh

# Start Nginx and PHP-FPM
CMD ["/var/www/render-start.sh"]
