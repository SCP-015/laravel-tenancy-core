# Dockerfile untuk Jenkins Agent dengan PHP + PCOV
FROM php:8.4-cli

# Install system dependencies + Node.js
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libzip-dev \
    libicu-dev \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Install Node.js LTS via NodeSource
RUN curl -fsSL https://deb.nodesource.com/setup_lts.x | bash - \
    && apt-get install -y nodejs \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Configure GD with JPEG and FreeType support
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring exif pcntl bcmath gd zip intl

# Install PCOV
RUN pecl install pcov && docker-php-ext-enable pcov

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Configure PHP - Set higher memory limit for code coverage
RUN echo "memory_limit = 512M" > /usr/local/etc/php/conf.d/memory-limit.ini && \
    echo "max_execution_time = 300" >> /usr/local/etc/php/conf.d/memory-limit.ini

# Verify installations
RUN php -m | grep pcov && \
    node --version && \
    npm --version && \
    php -i | grep memory_limit
