FROM gyselroth/tubee:php7.2-fpm-v8js

RUN mkdir -p /usr/share/man/man1/ && echo TLS_REQCERT never > /etc/ldap/ldap.conf

RUN apt-get update && apt-get install -y \
  libldb-dev \
  libldap2-dev \
  libxml2-dev \
  libcurl4-openssl-dev \
  libssl-dev \
  libzip-dev \
  libicu-dev \
  libsmbclient-dev \
  apt-transport-https \
  libmagickwand-dev \
  unixodbc-dev \
  build-essential \
  curl \
  gnupg \
  git \
  chrpath \
  python \
  nginx \
  smbclient \
  libglib2.0-dev \
  ca-certificates-java \
  && rm -rf /var/lib/apt/lists/*

RUN curl https://packages.microsoft.com/keys/microsoft.asc | apt-key add - \
    && curl https://packages.microsoft.com/config/debian/8/prod.list > /etc/apt/sources.list.d/mssql-release.list \
    && apt-get update \
    && ACCEPT_EULA=Y apt-get install -y unixodbc unixodbc-dev msodbcsql wget dialog  \
    && ln -s /usr/lib/x86_64-linux-gnu/libsybdb.a /usr/lib/ \
    && ln -s /usr/lib/x86_64-linux-gnu/libsybdb.so /usr/lib/ \
    && wget http://security.debian.org/debian-security/pool/updates/main/o/openssl/libssl1.0.0_1.0.1t-1+deb8u11_amd64.deb \
    && dpkg -i *.deb

ENV DEBIAN_FRONTEND noninteractive
RUN apt-get update && apt-get install -y locales \
    && echo "en_US.UTF-8 UTF-8" > /etc/locale.gen \
    && locale-gen en_US.UTF-8 \
    && dpkg-reconfigure locales \
    && /usr/sbin/update-locale LANG=en_US.UTF-8

ENV LANG en_US.UTF-8
ENV LANGUAGE en_US:en
ENV LC_ALL en_US.UTF-8

RUN ln -s /usr/lib/x86_64-linux-gnu/libldap.so /usr/lib/libldap.so
RUN docker-php-ext-install ldap xml opcache curl zip intl sockets pcntl sysvmsg mysqli

RUN pecl install mongodb \
    && pecl install apcu \
    && pecl install imagick \
    && pecl install smbclient \
    && pecl install sqlsrv \
    && pecl install pdo_sqlsrv \
    && docker-php-ext-enable mongodb apcu imagick smbclient pcntl sqlsrv pdo_sqlsrv mysqli

RUN mkdir /etc/ssl/tubee \
  && openssl genrsa -des3 -passout pass:x12345 -out server.pass.key 2048 \
  && openssl rsa -passin pass:x12345 -in server.pass.key -out key.pem \
  && rm server.pass.key \
  && openssl req -new -key key.pem -out server.csr -subj "/C=CH//L=Zurich/O=Balloon/CN=tubee.local" \
  && openssl x509 -req -days 365 -in server.csr -signkey key.pem -out chain.pem \
  && rm server.csr \
  && mv key.pem /etc/ssl/tubee/ \
  && mv chain.pem /etc/ssl/tubee/

RUN mkdir /usr/share/tubee && mkdir /usr/share/tubee/bin/console -p && mkdir /etc/tubee
COPY packaging/nginx.conf /etc/nginx/conf.d/tubee.conf
COPY src/lib /usr/share/tubee/src/lib
COPY vendor /usr/share/tubee/vendor
COPY src/.container.config.php /usr/share/tubee/src
COPY src/cgi-bin/cli.php /usr/share/tubee/bin/console/tubeecli
COPY src/httpdocs /usr/share/tubee/bin/httpdocs
COPY config/config.yaml.dist /etc/tubee/
COPY config/config.yaml.docker.dist /etc/tubee/config.docker.yaml
RUN ln -s /usr/share/tubee/bin/console/tubeecli /usr/bin/tubeecli

RUN echo "pm.max_children = 500" >> /usr/local/etc/php-fpm.d/www.conf.default \
  && echo "pm.max_children = 500" >> /usr/local/etc/php-fpm.d/www.conf \
  && sed 's/unix:\/run\/php\/php7.2-fpm.sock/127.0.0.1:9000/g' -i /etc/nginx/conf.d/tubee.conf

ENV TUBEE_PATH /usr/share/tubee
ENV TUBEE_CONFIG_DIR /etc/tubee

EXPOSE 443 9000
CMD service nginx start && php-fpm;
