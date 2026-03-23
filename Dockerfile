FROM php:8.1-fpm

# Установка необходимых пакетов
RUN apt-get update && apt-get install -y \
    wget gnupg2 lsb-release \
    zip unzip git curl libonig-dev libxml2-dev libpq-dev \
    pkg-config libzip-dev \
    # + для GD
    libpng-dev libjpeg-dev libfreetype6-dev

# Установка PHP-расширений
# GD и bcmath для поддержки Excel
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install pdo pdo_pgsql mbstring xml zip gd bcmath

# Установка Composer 
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Установка Xdebug
RUN pecl install xdebug \
    && docker-php-ext-enable xdebug

# Настройка Xdebug через ENV переменные и ini
ENV PHP_IDE_CONFIG="serverName=amazon-v1"
ENV XDEBUG_MODE=debug
ENV XDEBUG_START_WITH_REQUEST=yes
ENV XDEBUG_CLIENT_HOST=host.docker.internal
ENV XDEBUG_CLIENT_PORT=9003

# Добавим ini-файл (опционально, если нужен файл)
RUN echo "zend_extension=xdebug.so" > /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.mode=debug" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.start_with_request=yes" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.client_host=host.docker.internal" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.client_port=9003" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.idekey=PHPSTORM" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.log=/tmp/xdebug.log" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini \
 && echo "xdebug.log_level=10" >> /usr/local/etc/php/conf.d/docker-php-ext-xdebug.ini


# Рабочая директория проекта
WORKDIR /var/www
COPY ./src /var/www
COPY entrypoint.sh /usr/local/bin/
# удалить переносы строк windows если они есть
RUN sed -i 's/\r$//' /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

# Указываем ENTRYPOINT
ENTRYPOINT ["entrypoint.sh"]

# Стандартная команда запуска
CMD ["php-fpm"]
