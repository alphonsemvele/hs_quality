# Thin layer on top of FrankenPHP that installs the PHP extensions Laravel
# needs (pdo_pgsql, pgsql, redis, gd, intl, bcmath, zip, opcache).
#
# Uses install-php-extensions (bundled in FrankenPHP) which handles
# extension-specific apt deps internally — sidesteps the manual apt issue
# we hit trying to do this from scratch.

FROM dunglas/frankenphp:latest-php8.3

RUN install-php-extensions \
    bcmath \
    gd \
    intl \
    opcache \
    pcntl \
    pdo_pgsql \
    pgsql \
    redis \
    zip

# Conservative PHP runtime tuning for load testing — surface real prod
# limits, not dev defaults.
RUN { \
        echo 'memory_limit = 256M'; \
        echo 'opcache.enable = 1'; \
        echo 'opcache.memory_consumption = 128'; \
        echo 'opcache.max_accelerated_files = 20000'; \
        echo 'opcache.validate_timestamps = 1'; \
        echo 'opcache.revalidate_freq = 0'; \
    } > /usr/local/etc/php/conf.d/zz-load-test.ini
