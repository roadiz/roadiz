ARG PHP_VERSION=7.2
ARG NGINX_VERSION=1.27.2
ARG MYSQL_VERSION=8.0.40

ARG COMPOSER_VERSION=1.10.27
ARG PHP_EXTENSION_INSTALLER_VERSION=2.6.0
ARG PHP_EXTENSION_REDIS_VERSION=6.1.0

ARG UID=1000
ARG GID=${UID}

#######
# PHP #
#######

FROM php:${PHP_VERSION}-fpm-buster AS php

LABEL org.opencontainers.image.authors="ambroise@rezo-zero.com eliot@rezo-zero.com"

ARG UID
ARG COMPOSER_VERSION
ARG PHP_EXTENSION_INSTALLER_VERSION
ARG PHP_EXTENSION_REDIS_VERSION

SHELL ["/bin/bash", "-e", "-o", "pipefail", "-c"]

ENV MYSQL_HOST=db
ENV MYSQL_PORT=3306

COPY --link docker/php/wait-for-it.sh /wait-for-it.sh
COPY --link docker/php/fpm.d/www.conf ${PHP_INI_DIR}-fpm.d/zz-www.conf

RUN <<EOF
apt-get --quiet update
apt-get --quiet --yes --purge --autoremove upgrade
# Packages - System
apt-get --quiet --yes --no-install-recommends --verbose-versions install \
    less \
    sudo \
    git
rm -rf /var/lib/apt/lists/*

# User
addgroup --gid ${UID} php
adduser --home /home/php --shell /bin/bash --uid ${UID} --gecos php --ingroup php --disabled-password php
echo "php ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/php

# App
install --verbose --owner php --group php --mode 0755 --directory /app

chmod +x /wait-for-it.sh
chown -R php:php /app

# Php extensions
curl -sSLf  https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions \
    --output /usr/local/bin/install-php-extensions
chmod +x /usr/local/bin/install-php-extensions
install-php-extensions \
    @composer-${COMPOSER_VERSION} \
    bcmath \
    exif \
    fileinfo \
    gd \
    gmp \
    iconv \
    intl \
    json \
    mbstring \
    opcache \
    openssl \
    pcntl \
    pdo_mysql \
    simplexml \
    xsl \
    zip
EOF

WORKDIR /app



###################
# PHP Development #
###################

FROM php AS php-dev

ENV APP_ENV=dev
ENV APP_RUNTIME_ENV=dev
ENV APP_DEBUG=1

# Configs
RUN ln -sf ${PHP_INI_DIR}/php.ini-development ${PHP_INI_DIR}/php.ini
COPY --link docker/php/conf.d/php.dev.ini ${PHP_INI_DIR}/conf.d/zz-app.ini
COPY --link --chmod=755 docker/php/docker-php-entrypoint-dev /usr/local/bin/docker-php-entrypoint

RUN <<EOF
apt-get --quiet update
apt-get --quiet --yes --purge --autoremove upgrade
# Packages - System
apt-get --quiet --yes --no-install-recommends --verbose-versions install make
rm -rf /var/lib/apt/lists/*
# Prepare folder to install composer credentials
install --owner=php --group=php --mode=755 --directory /home/php/.composer
EOF

VOLUME /app

USER php


#########
# Nginx #
#########

FROM nginx:${NGINX_VERSION}-bookworm AS nginx

LABEL org.opencontainers.image.authors="ambroise@rezo-zero.com eliot@rezo-zero.com"

ARG UID
ARG GID

SHELL ["/bin/bash", "-e", "-o", "pipefail", "-c"]

RUN <<EOF
# Packages
apt-get --quiet update
apt-get --quiet --yes --purge --autoremove upgrade
apt-get --quiet --yes --no-install-recommends --verbose-versions install \
    less \
    sudo
rm -rf /var/lib/apt/lists/*

# User
usermod --uid ${UID} nginx
groupmod --gid ${GID} nginx
echo "nginx ALL=(ALL) NOPASSWD:ALL" > /etc/sudoers.d/nginx

# App
install --verbose --owner nginx --group nginx --mode 0755 --directory /app
EOF

# Silence entrypoint logs
ENV NGINX_ENTRYPOINT_QUIET_LOGS=1

# Config
COPY --link docker/nginx/nginx.conf               /etc/nginx/nginx.conf
COPY --link docker/nginx/mime.types               /etc/nginx/mime.types
COPY --link docker/nginx/conf.d/_gzip.conf        /etc/nginx/conf.d/_gzip.conf
COPY --link docker/nginx/conf.d/_security.conf    /etc/nginx/conf.d/_security.conf

WORKDIR /app


##############
# Nginx DEV  #
##############

FROM nginx AS nginx-dev

COPY --link docker/nginx/conf.d/default.conf  /etc/nginx/conf.d/default.conf

# Declare a volume for development
VOLUME /app


#########
# MySQL #
#########

FROM mysql:${MYSQL_VERSION} AS mysql

LABEL org.opencontainers.image.authors="ambroise@rezo-zero.com eliot@rezo-zero.com"

ARG UID
ARG GID

SHELL ["/bin/bash", "-e", "-o", "pipefail", "-c"]

RUN <<EOF
usermod -u ${UID} mysql
groupmod -g ${GID} mysql
EOF

COPY --link docker/mysql/performances.cnf /etc/mysql/conf.d/performances.cnf

VOLUME /var/lib/mysql
