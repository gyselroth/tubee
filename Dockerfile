FROM --platform=linux/amd64 gyselroth/tubee:php8.3-fpm-v8js

ENV DEBIAN_FRONTEND=noninteractive

# Build-Abhängigkeiten und Bibliotheken
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       $PHPIZE_DEPS \
       ca-certificates curl git gnupg \
       libldap2-dev libxml2-dev libcurl4-openssl-dev libssl-dev \
       libzip-dev libicu-dev libsmbclient-dev libmagickwand-dev \
       unixodbc-dev smbclient locales \
    && mkdir -p /etc/ldap \
    && echo "TLS_REQCERT never" > /etc/ldap/ldap.conf \
    && rm -rf /var/lib/apt/lists/*

# Microsoft SQL Server ODBC-Treiber
# Prüfen, ob es unter packages.microsoft.com/debian/13 ein Repo gibt; sonst 12 verwenden
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
       | gpg --dearmor -o /usr/share/keyrings/microsoft-prod.gpg \
    && echo "deb [arch=amd64 signed-by=/usr/share/keyrings/microsoft-prod.gpg] https://packages.microsoft.com/debian/12/prod bookworm main" \
       > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y --no-install-recommends msodbcsql18 \
    && rm -rf /var/lib/apt/lists/*

# Lokalisierung
RUN echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen en_US.UTF-8 \
    && /usr/sbin/update-locale LANG=en_US.UTF-8

ENV LANG=en_US.UTF-8
ENV LANGUAGE=en_US:en
ENV LC_ALL=en_US.UTF-8

# PHP-Erweiterungen (xml und curl sind im PHP-Image bereits eingebaut)
RUN docker-php-ext-install -j"$(nproc)" \
       ldap \
       opcache \
       zip \
       intl \
       sockets \
       pcntl \
       sysvmsg \
       mysqli

# PECL-Erweiterungen
# mongodb: Version zur Composer-Bibliothek passend wählen (siehe composer.lock)
RUN pecl install mongodb apcu imagick smbclient sqlsrv pdo_sqlsrv \
    && docker-php-ext-enable \
       mongodb \
       apcu \
       imagick \
       smbclient \
       sqlsrv \
       pdo_sqlsrv

# SSL-Zertifikate erstellen
RUN mkdir -p /etc/ssl/tubee \
    && openssl genrsa -des3 -passout pass:x12345 -out server.pass.key 2048 \
    && openssl rsa -passin pass:x12345 -in server.pass.key -out key.pem \
    && rm server.pass.key \
    && openssl req -new -key key.pem -out server.csr \
       -subj "/C=CH/L=Zurich/O=Balloon/CN=tubee.local" \
    && openssl x509 -req -days 365 -in server.csr \
       -signkey key.pem -out chain.pem \
    && rm server.csr \
    && mv key.pem /etc/ssl/tubee/ \
    && mv chain.pem /etc/ssl/tubee/

# Tubee
RUN mkdir -p /usr/share/tubee/bin/console /etc/tubee

COPY src/lib /usr/share/tubee/src/lib
COPY vendor /usr/share/tubee/vendor
COPY src/.container.config.php /usr/share/tubee/src/
COPY src/cgi-bin/cli.php /usr/share/tubee/bin/console/tubeecli
COPY src/httpdocs /usr/share/tubee/bin/httpdocs
COPY config/config.yaml.dist /etc/tubee/

RUN ln -s /usr/share/tubee/bin/console/tubeecli /usr/bin/tubeecli

# PHP-FPM konfigurieren
RUN echo "pm.max_children = 500" >> /usr/local/etc/php-fpm.d/www.conf.default \
    && echo "pm.max_children = 500" >> /usr/local/etc/php-fpm.d/www.conf

RUN cp /usr/local/etc/php/php.ini-development /usr/local/etc/php/php.ini \
    && sed -i -e 's/^ *memory_limit.*/memory_limit = 512M/g' \
       /usr/local/etc/php/php.ini

RUN php -r "copy('https://getcomposer.org/installer', 'composer-setup.php');" \
 && php composer-setup.php --filename=composer --install-dir=/usr/bin \
 && php -r "unlink('composer-setup.php');"

ENV TUBEE_PATH=/usr/share/tubee
ENV TUBEE_CONFIG_DIR=/etc/tubee
