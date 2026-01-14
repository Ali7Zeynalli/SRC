FROM php:8.1-apache

# Update and install dependencies
RUN apt-get update && apt-get install -y \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    libzip-dev \
    libxml2-dev \
    libicu-dev \
    libonig-dev \
    libldap2-dev \
    libcurl4-openssl-dev \
    unzip \
    zip \
    curl \
    openssl \
    && rm -rf /var/lib/apt/lists/*

# Configure GD
RUN docker-php-ext-configure gd --with-freetype --with-jpeg

# Configure LDAP
RUN docker-php-ext-configure ldap --with-ldap

# Install PHP Extensions (matching typical XAMPP)
RUN docker-php-ext-install \
    gd \
    mysqli \
    pdo \
    pdo_mysql \
    zip \
    soap \
    intl \
    mbstring \
    bcmath \
    calendar \
    exif \
    gettext \
    ldap

# Enable Apache Modules
RUN a2enmod ssl rewrite headers expires

# Create SSL directory
RUN mkdir -p /etc/ssl/private

# Generate Self-Signed SSL Certificate
RUN openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/apache-selfsigned.key \
    -out /etc/ssl/certs/apache-selfsigned.crt \
    -subj "/C=AZ/ST=Baku/L=Baku/O=Development/CN=localhost"

# Set permissions
RUN chmod 600 /etc/ssl/private/apache-selfsigned.key

# Create temp directories with proper permissions
RUN mkdir -p /var/www/html/temp/secure_store && \
    chown -R www-data:www-data /var/www/html/temp && \
    chmod -R 755 /var/www/html/temp

# LDAP SSL Configuration - disable certificate verification for development
RUN mkdir -p /etc/ldap && echo "TLS_REQCERT never" > /etc/ldap/ldap.conf

# Start Apache
CMD ["apache2-foreground"]
