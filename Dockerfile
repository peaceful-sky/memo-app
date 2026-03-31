FROM alpine:3.21

# 한국 미러로 변경
RUN printf 'https://mirror.krfoss.org/alpine/v3.21/main\nhttps://mirror.krfoss.org/alpine/v3.21/community\n' \
    > /etc/apk/repositories

RUN apk add --no-cache \
    nginx \
    php84 \
    php84-fpm \
    php84-pdo \
    php84-pdo_sqlite \
    php84-sqlite3 \
    php84-json \
    php84-session \
    php84-mbstring \
    php84-openssl \
    php84-ctype \
    sqlite \
    tzdata \
    && ln -sf /usr/bin/php84 /usr/bin/php

# Timezone
ENV TZ=Asia/Seoul
RUN cp /usr/share/zoneinfo/Asia/Seoul /etc/localtime

# nginx config
COPY nginx/nginx.conf /etc/nginx/nginx.conf
COPY nginx/default.conf /etc/nginx/http.d/default.conf

# php-fpm config
COPY php/php-fpm.conf /etc/php84/php-fpm.d/www.conf

# App source
COPY src/ /var/www/html/

# DB directory
RUN mkdir -p /var/db && chmod 777 /var/db
RUN mkdir -p /var/www/html && chown -R nginx:nginx /var/www/html
RUN mkdir -p /run/php84 && chown nginx:nginx /run/php84
RUN mkdir -p /run/nginx

# Init script
COPY docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

EXPOSE 80

ENTRYPOINT ["/docker-entrypoint.sh"]
