FROM wodby/drupal-php:8.3-4.69.0

ARG INSTALL_DEV_DEPENDENCIES=false

USER root
RUN set -eux \
  ; \
  \
  apk add --no-cache \
    libstdc++ \
    libx11 \
    libxrender \
    libxext \
    ca-certificates \
    gnumeric \
  \
;

COPY --chown=wodby:wodby . /var/www/html

WORKDIR /var/www/html

USER wodby
RUN set -eux; \
  export COMPOSER_HOME="$(mktemp -d)"; \
  if [ "$INSTALL_DEV_DEPENDENCIES" = "true" ] || [ "$INSTALL_DEV_DEPENDENCIES" = "1" ]; then \
    composer install --ignore-platform-reqs --no-cache --no-ansi --no-interaction --no-progress; \
  else \
    composer install --ignore-platform-reqs --no-cache --no-ansi --no-interaction --no-progress --no-dev; \
  fi
