FROM mysql:8.0
MAINTAINER Ambroise Maupate <ambroise@rezo-zero.com>

ARG USER_UID=1000

RUN usermod -u ${USER_UID} mysql \
    && groupmod -g ${USER_UID} mysql \
    && echo "USER_UID: ${USER_UID}\n"

