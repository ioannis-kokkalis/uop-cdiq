FROM php:8.2-apache

# ---

RUN apt-get update

# ---

RUN apt-get install -y libpq-dev
RUN docker-php-ext-install pdo pdo_pgsql

# ---

RUN a2enmod rewrite
RUN a2enmod ssl

# ---

ARG SERVER_SSL_CERT_FILE
ARG SERVER_SSL_PKEY_FILE

COPY ./cert "$SERVER_SSL_CERT_FILE"
COPY ./pkey "$SERVER_SSL_PKEY_FILE"

# ---

COPY ./conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default
RUN a2dissite default-ssl