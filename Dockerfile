FROM php:8.2.35-fpm

# ── PHP Extension Installer (handles deps automatically) ──────────────────────
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    /usr/local/bin/install-php-extensions
RUN chmod +x /usr/local/bin/install-php-extensions

# ── System dependencies ───────────────────────────────────────────────────────
RUN apt-get update && apt-get install -y \
    git \
    curl \
    zip \
    unzip \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

# ── PHP Extensions ────────────────────────────────────────────────────────────
# install-php-extensions handles build order & system libs automatically
RUN install-php-extensions \
    pdo_mysql \
    mbstring \
    exif \
    pcntl \
    bcmath \
    gd \
    zip \
    intl \
    xml \
    xsl \
    dom \
    simplexml \
    xmlreader \
    xmlwriter \
    tokenizer \
    fileinfo \
    opcache

# ── Node.js 20 (Official Binary - include npm automatically) ───────────────────
RUN curl -fsSL https://nodejs.org/dist/v20.18.0/node-v20.18.0-linux-x64.tar.gz \
    | tar -xz -C /usr/local --strip-components=1

# ── Composer ──────────────────────────────────────────────────────────────────
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# ── Working directory ─────────────────────────────────────────────────────────
WORKDIR /var/www

# ── Install PHP dependencies ──────────────────────────────────────────────────
COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-interaction \
    --prefer-dist \
    --ignore-platform-reqs \
    --optimize-autoloader

# ── Install Node dependencies ─────────────────────────────────────────────────
COPY package.json package-lock.json ./
RUN npm ci

# ── Copy application source ───────────────────────────────────────────────────
COPY . .

# ── Create stub .env so Laravel bootstrap doesn't crash during build ──────────
RUN cp .env.example .env

# ── Build frontend assets ─────────────────────────────────────────────────────
RUN npm run build

# ── Permissions ───────────────────────────────────────────────────────────────
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache \
    && chmod -R 775 /var/www/storage /var/www/bootstrap/cache

# ── Entrypoint ────────────────────────────────────────────────────────────────
COPY docker/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000
ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
