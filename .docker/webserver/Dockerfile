FROM php:8.5-apache AS production

COPY .docker/webserver/000-default.conf /etc/apache2/sites-available/000-default.conf
COPY .docker/webserver/php-settings.ini /usr/local/etc/php/conf.d/zzz-ct.ini

RUN apt update -y \
 && apt upgrade -y \
 && apt install -y curl zlib1g-dev libpng-dev libjpeg-dev libzip-dev libpq-dev fonts-noto-core fonts-noto-cjk \
 && a2enmod rewrite \
 && docker-php-ext-configure gd --with-jpeg \
 && docker-php-ext-install gd pdo pdo_pgsql zip \
 && groupadd -g 1000 app \
 && useradd -u 1000 -g app -G www-data -m app \
 && mkdir /opt/app \
 && chown app:www-data /opt/app

WORKDIR /opt/app

FROM production AS development

COPY .docker/webserver/php-dev.ini /usr/local/etc/php/conf.d/zzz-ct-dev.ini
COPY .docker/webserver/install-composer.sh /install-composer.sh

RUN apt update -y \
 && apt install -y git vim unzip \
 && pecl install excimer xdebug \
 && docker-php-ext-enable xdebug \
 && /install-composer.sh \
 && rm /install-composer.sh \
 && ls -la \
 && mv composer.phar /usr/local/bin/composer \
 && composer self-update
