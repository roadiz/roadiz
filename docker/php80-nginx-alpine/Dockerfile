FROM roadiz/php80-nginx-alpine
MAINTAINER Ambroise Maupate <ambroise@rezo-zero.com>

ARG USER_UID=1000
ENV COMPOSER_ALLOW_SUPERUSER=1
ENV APP_ENV=dev
ENV APP_CACHE=0

RUN apk add --no-cache shadow make git \
    && usermod -u ${USER_UID} www-data \
    && groupmod -g ${USER_UID} www-data \
    && composer --version \
    && ln -s /usr/share/zoneinfo/Europe/Paris /etc/localtime \
    && "date"

# Display errors
ADD php.ini /usr/local/etc/php/php.ini
# Added Roadiz messenger for async tasks
ADD supervisor.ini /etc/supervisor.d/services.ini
ADD zz-docker.conf /usr/local/etc/php-fpm.d/zz-docker.conf
ADD nginx /etc/nginx

VOLUME /var/www/html
WORKDIR /var/www/html

RUN ln -s /usr/share/nginx/html/bin/roadiz /usr/local/bin/roadiz \
    && chown -R www-data:www-data /var/www/html/

ENTRYPOINT exec /usr/bin/supervisord -n -c /etc/supervisord.conf
