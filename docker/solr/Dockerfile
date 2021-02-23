FROM solr:8-slim
MAINTAINER Ambroise Maupate <ambroise@rezo-zero.com>

ARG USER_UID=1000

USER root
RUN set -ex; \
    usermod -u ${USER_UID} "$SOLR_USER"; \
    groupmod -g ${USER_UID} "$SOLR_GROUP";

USER $SOLR_USER
