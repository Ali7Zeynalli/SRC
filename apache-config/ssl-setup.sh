#!/bin/bash

# Create SSL directory if it doesn't exist
mkdir -p /etc/ssl/private

# Generate self-signed certificate
openssl req -x509 -nodes -days 365 -newkey rsa:2048 \
    -keyout /etc/ssl/private/apache-selfsigned.key \
    -out /etc/ssl/certs/apache-selfsigned.crt \
    -subj "/C=AZ/ST=Baku/L=Baku/O=Development/CN=localhost"

# Set appropriate permissions
chmod 600 /etc/ssl/private/apache-selfsigned.key

# Enable Apache SSL module
a2enmod ssl
a2enmod rewrite
a2enmod headers

# Restart Apache
apache2ctl restart

echo "SSL Certificate generated and configured successfully"
