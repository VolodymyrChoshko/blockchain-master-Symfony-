FROM ubuntu/nginx:1.18-22.04_beta

# Install node.js
RUN apt update && \
    apt-get upgrade -y && \
    apt-get install software-properties-common -y && \
    apt-get install -y curl gpg-agent nano && \
    curl -sL https://deb.nodesource.com/setup_14.x | bash - && \
    apt-get update && apt install -y nodejs

# Install yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
    echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list && \
    apt-get update && apt -o Dpkg::Options::="--force-overwrite" install -y yarn

# Install php 7.2
RUN add-apt-repository ppa:ondrej/php && \
    apt-get update && \
    apt-get install php7.2-fpm php7.2-cli -y

# exts
RUN apt-get install -y \
    php7.2-common \
    php7.2-mongodb \
    php7.2-curl \
    php7.2-intl \
    php7.2-soap \
    php7.2-xml \
    php7.2-bcmath \
    php7.2-mysql \
    php7.2-amqp \
    php7.2-mbstring \
    php7.2-ldap \
    php7.2-zip \
    php7.2-json \
    php7.2-xml \
    php7.2-xmlrpc \
    php7.2-gmp \
    php7.2-ldap \
    php7.2-gd \
    php7.2-redis \
    php7.2-xdebug && \
    echo "extension=apcu.so" | tee -a /etc/php/7.2/mods-available/cache.ini

# Install composer
RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
    && php composer-setup.php --install-dir=bin --filename=composer \
    && php -r "unlink('composer-setup.php');"

# Copy configuration and source files
COPY docker/etc /etc
RUN rm /etc/nginx/sites-enabled/default
# COPY --chown=www-data:www-data . /var/www

WORKDIR /var/www/blocksedit

# Install dependencies
# RUN yarn install && yarn run build
# ENV COMPOSER_ALLOW_SUPERUSER=1
# RUN composer update && composer dumpautoload -o

# Copy entrypoint script
COPY docker/entrypoint.sh /entrypoint.sh
RUN chmod 755 /entrypoint.sh
ENTRYPOINT ["/entrypoint.sh"]
