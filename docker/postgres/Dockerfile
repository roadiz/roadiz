FROM postgres:12
MAINTAINER Ambroise Maupate <ambroise@rezo-zero.com>

ARG USER_UID=1000

RUN usermod -u ${USER_UID} postgres \
    && groupmod -g ${USER_UID} postgres \
    && echo "USER_UID: ${USER_UID}\n"

