FROM kirschbaumdevelopment/laravel-test-runner:8.2

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev

EXPOSE 8000
CMD php artisan migrate --force && php artisan serve --host=0.0.0.0 --port=$PORT
