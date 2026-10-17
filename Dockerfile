FROM gyselroth/tubee:php7.2-fpm-v8js

ENV DEBIAN_FRONTEND=noninteractive

RUN mkdir -p /usr/share/man/man1/ \
    && echo "TLS_REQCERT never" > /etc/ldap/ldap.conf

# Debian Stretch: Archiv-Repositories konfigurieren
RUN rm -f /etc/apt/sources.list.d/*.list \
          /etc/apt/sources.list.d/*.sources \
    && printf '%s\n' \
       'deb [trusted=yes] http://archive.debian.org/debian stretch main' \
       'deb [trusted=yes] http://archive.debian.org/debian-security stretch/updates main' \
       > /etc/apt/sources.list \
    && rm -rf /var/lib/apt/lists/* \
    && apt-get \
       -o Acquire::Check-Valid-Until=false \
       -o Acquire::http::No-Cache=true \
       -o Acquire::http::Pipeline-Depth=0 \
       update \
    && apt-get install -y --no-install-recommends \
       gnupg \
       libldb-dev \
       libldap2-dev \
       libxml2-dev \
       libcurl4-openssl-dev \
       libssl-dev \
       libzip-dev \
       libicu-dev \
       libsmbclient-dev \
       apt-transport-https=1.4.10 \
       libmagickwand-dev \
       unixodbc-dev \
       build-essential \
       curl \
       git \
       chrpath \
       python \
       smbclient \
       libglib2.0-dev \
       ca-certificates-java \
    && rm -rf /var/lib/apt/lists/*

# Microsoft SQL Server ODBC-Treiber
RUN curl -fsSL https://packages.microsoft.com/keys/microsoft.asc \
       -o /tmp/microsoft.asc \
    && apt-key add /tmp/microsoft.asc \
    && rm /tmp/microsoft.asc \
    && curl -fsSL https://packages.microsoft.com/config/debian/9/prod.list \
       -o /etc/apt/sources.list.d/mssql-release.list \
    && apt-get \
       -o Acquire::Check-Valid-Until=false \
       -o Acquire::http::No-Cache=true \
       -o Acquire::http::Pipeline-Depth=0 \
       update \
    && ACCEPT_EULA=Y apt-get install -y \
       unixodbc \
       unixodbc-dev \
       msodbcsql17 \
       wget \
       dialog \
    && ln -s /usr/lib/x86_64-linux-gnu/libsybdb.a /usr/lib/ \
    && ln -s /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/ \
    && wget -O /tmp/libssl1.0.0.deb \
       http://archive.debian.org/debian-archive/debian-security/pool/updates/main/o/openssl/libssl1.0.0_1.0.1t-1+deb8u12_amd64.deb \
    && dpkg -i /tmp/libssl1.0.0.deb; \
    rm -f /tmp/libssl1.0.0.deb

# Lokalisierung
RUN apt-get \
       -o Acquire::Check-Valid-Until=false \
       -o Acquire::http::No-Cache=true \
       update \
    && apt-get install -y locales \
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen en_US.UTF-8 \
    && dpkg-reconfigure -f noninteractive locales \
    && /usr/sbin/update-locale LANG=en_US.UTF-8 \
    && rm -rf /var/lib/apt/lists/*

ENV LANG=en_US.UTF-8
ENV LANGUAGE=en_US:en
ENV LC_ALL=en_US.UTF-8

# PHP-Erweiterungen
RUN ln -s /usr/lib/x86_64-linux-gnu/libldap.so /usr/lib/libldap.so \
    && docker-php-ext-install \
       ldap \
       xml \
       opcache \
       curl \
       zip \
       intl \
       sockets \
       pcntl \
       sysvmsg \
       mysqli

# PECL-Erweiterungen
RUN pecl install mongodb-1.16.2 \
    && pecl install apcu \
    && pecl install imagick-3.4.4 \
    && pecl install smbclient-1.1.2 \
    && pecl install sqlsrv-5.8.1 \
    && pecl install pdo_sqlsrv-5.8.1 \
    && docker-php-ext-enable \
       mongodb \
       apcu \
       imagick \
       smbclient \
       pcntl \
       sqlsrv \
       pdo_sqlsrv \
       mysqli

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

ENV TUBEE_PATH=/usr/share/tubee
ENV TUBEE_CONFIG_DIR=/etc/tubee
