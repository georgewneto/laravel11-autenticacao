#!/bin/bash

# This script handles SSL certificate generation
# It must be run with the appropriate domain name parameter

DOMAIN=${1:-localhost}
SSL_DIR="/var/www/ssl"

# Function to generate self-signed certificates with mkcert if Let's Encrypt fails
generate_self_signed() {
    echo "Generating development certificates with mkcert for $DOMAIN..."
    cd $SSL_DIR
    mkcert -install
    mkcert -key-file server.key -cert-file server.crt $DOMAIN "www.$DOMAIN" localhost 127.0.0.1 ::1
    echo "Development certificates generated successfully."
}

# Function to request Let's Encrypt certificates
request_lets_encrypt() {
    echo "Requesting Let's Encrypt certificates for $DOMAIN..."

    # Stop web server if running
    pkill -f "php artisan serve" || true

    # Request the certificate using standalone mode
    certbot certonly --standalone \
        --preferred-challenges http \
        --agree-tos \
        --no-eff-email \
        --email admin@$DOMAIN \
        -d $DOMAIN -d www.$DOMAIN

    # If successful, copy the certificates to our ssl directory
    if [ $? -eq 0 ]; then
        echo "Copying Let's Encrypt certificates..."
        cp /etc/letsencrypt/live/$DOMAIN/fullchain.pem $SSL_DIR/server.crt
        cp /etc/letsencrypt/live/$DOMAIN/privkey.pem $SSL_DIR/server.key
        echo "Let's Encrypt certificates installed successfully!"
        return 0
    else
        echo "Let's Encrypt certificate request failed."
        return 1
    fi
}

# Main execution
echo "Setting up SSL certificates for $DOMAIN"

# Create SSL directory if it doesn't exist
mkdir -p $SSL_DIR

# Try to get Let's Encrypt certificates if we're in production
if [ "$DOMAIN" != "localhost" ] && [ "$DOMAIN" != "127.0.0.1" ]; then
    request_lets_encrypt

    # If Let's Encrypt fails, fall back to self-signed
    if [ $? -ne 0 ]; then
        generate_self_signed
    fi
else
    # In development, just use self-signed
    generate_self_signed
fi

# Ensure proper permissions
chmod 600 $SSL_DIR/server.key
chmod 644 $SSL_DIR/server.crt

echo "SSL certificate setup complete!"
