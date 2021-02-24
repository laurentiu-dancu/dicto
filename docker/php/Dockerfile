FROM php:8.0.2-apache

RUN apt-get update && apt-get install -y curl git zip unzip openssh-client nano vim libssh-dev libpng-dev \
zip unzip zlib1g-dev libpq-dev libmcrypt-dev libzip-dev libicu-dev libxml2-dev --no-install-recommends
RUN docker-php-ext-configure intl
RUN docker-php-ext-install gd pdo pdo_mysql zip gettext intl bcmath dom
ADD ./php.ini /usr/local/etc/php/conf.d/php.ini

# Install Composer.
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer \
    && apt-get autoremove -y && apt-get clean && apt-get autoclean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN pecl install xdebug && docker-php-ext-enable xdebug

RUN composer global require drush/drush:^10
ENV PATH "$PATH:/root/.composer/vendor/bin"

ADD ./php.ini /usr/local/etc/php/conf.d/php.ini
ADD ./xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

# Disable default VH on apache
RUN a2dissite 000-default.conf
RUN a2enmod rewrite

# Prepare fake SSL certificate
RUN apt-get update
RUN apt-get install -y ssl-cert

# Setup Apache2 mod_ssl
RUN a2enmod ssl

# Copy and enable the VH
COPY ./drupal.conf /etc/apache2/sites-available/
RUN a2ensite drupal.conf
RUN service apache2 restart

WORKDIR /var/www/html/web
