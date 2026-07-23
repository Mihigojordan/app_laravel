# ---- Frontend asset build stage ----
# public/js/bundle (webpack chunk files) is gitignored — it's a build
# artifact, not source. It must be generated here at image-build time,
# otherwise the lazy-loaded route chunks (dashboard.js, customizer.js, etc.)
# simply don't exist in the deployed container and requests for them fall
# through to Laravel's HTML error page, breaking every non-eager route.
FROM node:20-alpine AS frontend

WORKDIR /app

COPY package.json package-lock.json ./
RUN npm ci

COPY webpack.mix.js ./
COPY resources ./resources
COPY public ./public

RUN npm run production

# ---- PHP application stage ----
FROM php:8.2-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    git \
    curl \
    unzip \
    zip \
    libzip-dev \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libonig-dev \
    libxml2-dev \
    libicu-dev

RUN docker-php-ext-configure gd --with-freetype --with-jpeg

RUN docker-php-ext-install \
    pdo \
    pdo_mysql \
    mysqli \
    mbstring \
    zip \
    exif \
    bcmath \
    gd \
    intl \
    calendar

RUN a2enmod rewrite headers

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./

# Install dependencies WITHOUT running Laravel scripts and WITHOUT generating
# the autoloader yet. The classmap in composer.json points at
# database/seeders and database/factories, which don't exist in the build
# context until the full `COPY . .` below runs. Generating the optimized
# autoloader here (as the old single-step install did) fails with:
#   "Could not scan for classes inside "database/seeders" ..."
RUN composer install \
    --no-dev \
    --prefer-dist \
    --no-interaction \
    --no-scripts \
    --no-autoloader

COPY . .

# Overlay the freshly built frontend assets on top of whatever stale
# public/js/*.min.js happened to be committed — the build output is the
# source of truth, not what's checked into git.
COPY --from=frontend /app/public/js ./public/js
COPY --from=frontend /app/public/mix-manifest.json ./public/mix-manifest.json

# Now that the full source tree (including database/seeders and
# database/factories) is present, generate the optimized autoloader.
RUN composer dump-autoload --no-dev --optimize --no-scripts

# Create .env if it doesn't exist and a template is available
RUN [ -f .env ] || [ ! -f .env.example ] || cp .env.example .env

RUN mkdir -p storage bootstrap/cache

RUN chmod -R 775 storage bootstrap/cache

RUN chown -R www-data:www-data storage bootstrap/cache

RUN sed -i 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/000-default.conf

COPY start.sh /start.sh
RUN chmod +x /start.sh

EXPOSE 80

CMD ["/start.sh"]