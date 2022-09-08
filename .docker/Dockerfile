ARG  PHP_VERSION=8.2

################################################################################

FROM php:${PHP_VERSION}

# composer settings
ARG COMPOSER_INSTALLER="https://getcomposer.org/installer"
ARG COMPOSER_SCRIPT="/tmp/composer-setup.php"
ARG PREBUILD_MODULES="intl"

ENV COMPOSER_ALLOW_SUPERUSER=1
ENV COMPOSER_HOME=/composer
ENV COMPOSER_HTACCESS_PROTECT=1
ENV COMPOSER_MAX_PARALLEL_HTTP=32

################################################################################

RUN set -x; \
    apt-get update ; apt-get install -y git libicu-dev zip unzip \
    && docker-php-ext-install -j$(nproc) ${PREBUILD_MODULES} \
    && docker-php-ext-enable ${PREBUILD_MODULES} 2>&1 \
    && php -r "copy('${COMPOSER_INSTALLER}', '${COMPOSER_SCRIPT}');" \
    && php $COMPOSER_SCRIPT --install-dir=/usr/bin --filename=composer \
    && chmod +xr /usr/bin/composer
