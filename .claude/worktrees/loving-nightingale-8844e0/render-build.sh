#!/usr/bin/env bash
# exit on error
set -o errexit

# Устанавливаем зависимости composer
composer install --no-dev --no-interaction --no-autoloader --no-scripts
composer install

# Устанавливаем зависимости npm и собираем фронтенд-ассеты (если они есть в Laravel)
# npm install
# npm run build

# Генерируем ключ приложения
php artisan key:generate --force

# Запускаем миграции базы данных
php artisan migrate --force

# Создаем символическую ссылку для хранилища
# Это критически важно, чтобы загруженные файлы были доступны по URL
php artisan storage:link
