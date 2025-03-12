FROM php:8.2-apache

# ---

RUN apt-get update && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*
# Clean the apt cache to reduce the image size after installing the packages

# ---

RUN a2enmod rewrite ssl

# ---

ARG SERVER_SSL_CERT_FILE
ARG SERVER_SSL_PKEY_FILE

COPY ./cert "$SERVER_SSL_CERT_FILE"
COPY ./pkey "$SERVER_SSL_PKEY_FILE"

# ---
# Copy the Apache configuration file and enable the site
COPY ./conf /etc/apache2/sites-available/000-default.conf
RUN a2ensite 000-default && a2dissite default-ssl

CMD chown -R www-data:www-data /var/www/html/resources/images/interviewer \
    && apache2-foreground

# END
