FROM kirschbaumdevelopment/laravel-test-runner:8.2

WORKDIR /var/www/html

COPY . .

RUN composer install --no-dev
RUN php artisan key:generate
RUN touch database/database.sqlite

EXPOSE 8000
CMD php artisan serve --host=0.0.0.0 --port=8000
