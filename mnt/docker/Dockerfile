FROM php:7.4-apache

RUN apt-get update && apt-get install -y wget git unzip \
    && pecl install xdebug-2.9.2 \
    && docker-php-ext-enable xdebug

ADD ./php.ini /usr/local/etc/php/php.ini

RUN wget https://getcomposer.org/installer -O - -q \
    | php -- --install-dir=/bin --filename=composer --quiet

RUN apt-get update && apt-get install --no-install-recommends -y \
    libzip-dev \
    zlibc \
    zlib1g \
    && docker-php-ext-configure zip  \
    && docker-php-ext-install zip

RUN apt-get install -y \
    libpq-dev \
    libmemcached-dev \
    curl \
    git

RUN echo 'xdebug.remote_port=9000' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_enable=1' >> /usr/local/etc/php/php.ini
RUN echo 'xdebug.remote_connect_back=1' >> /usr/local/etc/php/php.ini

# Install package dependencies
RUN apt update && apt install -y libmagickwand-dev git libzip-dev unzip libgmp-dev

RUN pecl install imagick
RUN docker-php-ext-enable imagick

# Enable default PHP extensions
RUN docker-php-ext-install mysqli pdo_mysql pcntl bcmath zip soap intl gd exif gmp

# Install node
RUN apt-get install -y gnupg2
RUN rm -rf /var/lib/apt/lists/ && curl -sL https://deb.nodesource.com/setup_10.x | bash -
RUN apt-get install nodejs -y

COPY /vhosts/default-vhost.conf /etc/apache2/sites-available/
RUN a2dissite 000-default.conf
RUN a2ensite default-vhost.conf
RUN a2enmod rewrite

RUN apt-get update && apt-get -y install cron nano

# Give execution rights on the cron job
RUN chmod 0644 /etc/cron.d
# Create the log file to be able to run tail
RUN touch /var/log/cron.log
# Activate cron for root
RUN (crontab; echo "#") | crontab

WORKDIR /var/www/html

