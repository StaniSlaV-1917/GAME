# Production-образ Laravel 10 для Fly.io
# База: serversideup/php — Alpine + nginx + php-fpm + s6-overlay,
# работает не от root, слушает 8080 (совпадает с Fly internal_port).
FROM serversideup/php:8.2-fpm-nginx-alpine

# ── PHP-расширения для Postgres / GD / intl / zip ──
USER root
RUN install-php-extensions pdo_pgsql pgsql gd intl zip bcmath
USER www-data

# ── Код приложения ──
WORKDIR /var/www/html
COPY --chown=www-data:www-data . /var/www/html

# ── Composer (production-зависимости + оптимизированный автолоад) ──
RUN composer install --no-dev --no-interaction --no-progress --optimize-autoloader

# ── Права на storage и bootstrap/cache ──
USER root
RUN chown -R www-data:www-data /var/www/html/storage /var/www/html/bootstrap/cache && \
    chmod -R 775 /var/www/html/storage /var/www/html/bootstrap/cache
USER www-data

# Миграции, кэши конфига/роутов/views делаются из release_command
# в fly.toml — это запускается ПОСЛЕ инжекции secrets, до старта веба.
EXPOSE 8080
