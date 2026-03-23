#!/bin/sh
set -e

# Если папка пустая (такое бывает при первом создании тома)
if [ ! -f "composer.json" ]; then
    echo "Creating new Laravel project..."
    composer create-project laravel/laravel .
fi

if [ ! -f .env ]; then
    echo "Создание .env файла..."
    cp .env.example .env
else
    # Настраиваем .env сразу под наш Docker-compose
    sed -i 's/DB_CONNECTION=mysql/DB_CONNECTION=pgsql/' .env
    sed -i 's/DB_HOST=127.0.0.1/DB_HOST=db/' .env
    sed -i "s/^DB_PORT=.*/DB_PORT=$DB_PORT/" .env
    sed -i "s/^DB_DATABASE=.*/DB_DATABASE=$DB_DATABASE/" .env
    sed -i "s/^DB_USERNAME=.*/DB_USERNAME=$DB_USERNAME/" .env
    sed -i "s/^DB_PASSWORD=.*/DB_PASSWORD=$DB_PASSWORD/" .env
fi

# Права доступа
chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

# Стандартные команды
# composer install
composer install --no-interaction --prefer-dist --optimize-autoloader

# Ожидание базы данных
echo "Waiting for database..."
until php -r "try { new PDO('pgsql:host=$DB_HOST;port=$DB_PORT;dbname=$DB_DATABASE', '$DB_USERNAME', '$DB_PASSWORD'); exit(0); } catch (Exception \$e) { exit(1); }"; do
    echo "Database is unavailable - sleeping"
    sleep 2
done
echo "Database is up - executing commands"

# php artisan key:generate --show # Если ключа нет
php artisan key:generate --force
php artisan migrate --force

exec "$@"