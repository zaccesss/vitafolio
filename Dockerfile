# production image for vitafolio: php 8.4 served by frankenphp on alpine. three stages, so the
# final image carries no node, no composer and no development dependencies. frankenphp on alpine
# was chosen after scanning: it carries far fewer known vulnerabilities than apache on debian.

# front-end assets
FROM node:26-alpine AS assets
WORKDIR /app
COPY package.json package-lock.json .npmrc ./
RUN npm ci --no-audit --no-fund
COPY vite.config.js ./
# tailwind scans the whole project for class names, so the php that renders html comes along too
COPY resources ./resources
COPY app ./app
COPY config ./config
COPY public ./public
RUN npm run build

# typst renders the tagged cv and letter pdfs. its official image is alpine based, so the binary
# runs on the alpine base below; pinned by digest so a rebuild always carries the same release
FROM ghcr.io/typst/typst:0.15.1@sha256:032e292249bcd378480cc7c142cfa324b63ef8aadeb88d7e7230320c4c9c422f AS typst

# the simplified chinese pdf font is too large for git, so it is fetched here and checked against
# its published sha-256 by the same script ci uses
FROM alpine:3 AS pdf-fonts
RUN apk add --no-cache curl
COPY scripts/fetch-pdf-fonts.sh /src/scripts/fetch-pdf-fonts.sh
RUN mkdir -p /src/resources/pdf/fonts && sh /src/scripts/fetch-pdf-fonts.sh

# php with the extensions the app needs, shared by the dependency stage and the final image
FROM dunglas/frankenphp:1-php8.4-alpine AS base
RUN install-php-extensions gd pdo_mysql zip intl bcmath opcache

FROM base AS vendor
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader --no-interaction --prefer-dist
COPY . .
RUN composer dump-autoload --optimize --classmap-authoritative --no-dev \
    && php artisan package:discover --ansi

# the image render runs
FROM base
ENV APP_ENV=production APP_DEBUG=false LOG_CHANNEL=stderr PORT=10000
COPY docker/Caddyfile /etc/caddy/Caddyfile
COPY docker/php.ini "$PHP_INI_DIR/conf.d/zz-vitafolio.ini"
COPY docker/start.sh /usr/local/bin/start
WORKDIR /app
COPY --from=vendor /app ./
COPY --from=assets /app/public/build ./public/build
COPY --from=typst /bin/typst /usr/local/bin/typst
COPY --from=pdf-fonts /src/resources/pdf/fonts/NotoSansSC-Regular.otf /src/resources/pdf/fonts/NotoSansSC-Bold.otf ./resources/pdf/fonts/
# everything runs as an unprivileged user on an unprivileged port, so nothing needs root. the
# binary's port-binding capability is removed too: hosts that start containers with no extra
# privileges, such as render, refuse to run a binary that asks for one
RUN setcap -r /usr/local/bin/frankenphp \
    && adduser -D -u 10001 vitafolio \
    && rm -f public/hot \
    && chown -R vitafolio:vitafolio storage bootstrap/cache /data/caddy /config/caddy \
    && chmod +x /usr/local/bin/start
USER vitafolio
EXPOSE 10000
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s CMD wget -qO /dev/null "http://127.0.0.1:${PORT}/up" || exit 1
CMD ["start"]
