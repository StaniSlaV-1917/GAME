# Production-образ Laravel 10 для Fly.io
# База: php:8.2-apache (мод_php) — Apache живёт как PID 1 одним процессом,
# никаких s6-overlay/supervisord, нет конфликта с Fly hallpass-init.
FROM php:8.2-apache

# ── Системные пакеты + PHP-расширения ──
# install-php-extensions — официальный installer от mlocati,
# собирает любые PECL/bundled расширения с зависимостями.
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions \
        pdo_pgsql \
        pgsql \
        gd \
        intl \
        zip \
        bcmath \
        opcache \
        exif \
        pcntl

# ── Apache: DocumentRoot → /var/www/html/public, rewrite, порт 80 → 8080,
#    AllowOverride All (без него Laravel'овый public/.htaccess игнорится
#    и роуты типа /up получают 404 от самого Apache, не доходя до Laravel) ──
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf && \
    sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf && \
    sed -i 's/Listen 80/Listen 8080/' /etc/apache2/ports.conf && \
    sed -i 's/<VirtualHost \*:80>/<VirtualHost *:8080>/' /etc/apache2/sites-available/000-default.conf && \
    a2enmod rewrite headers && \
    # Включаем поддержку .htaccess для public/ (Laravel routing)
    { \
      echo '<Directory /var/www/html/public>'; \
      echo '    Options Indexes FollowSymLinks'; \
      echo '    AllowOverride All'; \
      echo '    Require all granted'; \
      echo '</Directory>'; \
    } > /etc/apache2/conf-available/laravel-public.conf && \
    a2enconf laravel-public && \
    # Глушим warning "Could not reliably determine the server fully qualified domain name"
    echo 'ServerName localhost' >> /etc/apache2/apache2.conf

# ── OPcache в production-режиме ──
RUN { \
    echo 'opcache.enable=1'; \
    echo 'opcache.enable_cli=0'; \
    echo 'opcache.memory_consumption=64'; \
    echo 'opcache.interned_strings_buffer=16'; \
    echo 'opcache.max_accelerated_files=20000'; \
    echo 'opcache.validate_timestamps=0'; \
    echo 'opcache.revalidate_freq=0'; \
    echo 'opcache.fast_shutdown=1'; \
    } > /usr/local/etc/php/conf.d/opcache.ini

# ── Composer (тащим из официального образа без локальной установки) ──
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

# ── Код приложения ──
WORKDIR /var/www/html
COPY . /var/www/html

# ── Composer install + права на runtime-папки Laravel ──
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader && \
    chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache

# Миграции, storage:link и кэши конфига/роутов/views делаются из
# release_command в fly.toml — после инжекции Fly secrets, до старта веба.
EXPOSE 8080
