FROM php:8.4-cli-alpine

# Install Node.js
RUN apk add --no-cache nodejs npm

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer files first
COPY composer.json composer.lock ./
RUN composer install --no-dev --optimize-autoloader --no-interaction --no-scripts

# Copy package files
COPY package.json package-lock.json ./
RUN npm ci

# Copy rest of files
COPY . .

# Build
RUN composer dump-autoload --optimize
RUN npm run build

# Permissions
RUN chmod -R 755 storage bootstrap/cache

# Copy startup script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 8000 2222

CMD ["/docker-entrypoint.sh"]
