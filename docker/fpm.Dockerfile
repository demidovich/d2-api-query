FROM demidovich/php-fpm:7.4-alpine

ARG UID=82
ARG GID=82
ENV UID=${UID:-82} \
    GID=${GID:-82} \
    PHP_COMPOSER_VERSION=2.0.9

RUN set -eux; \
    if [ $UID -ne 82 ]; then \
        usermod -u ${UID} www-data; \
    fi; \
    if [ $GID -ne 82 ]; then \
        groupmod -g ${GID} www-data; \
    fi; \
    cp -f "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"; \
    install-composer.sh $PHP_COMPOSER_VERSION; \
    chown -R www-data:www-data /composer;

USER "www-data"

WORKDIR /app
