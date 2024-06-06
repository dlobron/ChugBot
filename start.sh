#!/bin/bash

set -uo pipefail

# Directory for self-signed certificates
CERT_DIR="Docker/certs"
CERT_FILE="$CERT_DIR/server.crt"
KEY_FILE="$CERT_DIR/server.key"

# Create the certificate directory if it doesn't exist
mkdir -p $CERT_DIR

# Generate self-signed certificates if they don't exist
if [[ ! -f "$CERT_FILE" || ! -f "$KEY_FILE" ]]; then
    echo "Generating self-signed certificates"
    openssl req -x509 -nodes -days 365 -newkey rsa:2048 -keyout $KEY_FILE -out $CERT_FILE -subj "/CN=localhost" -addext "subjectAltName = DNS:localhost,IP:127.0.0.1"

    # Attempt to add the certificate to the trusted store
    if [[ "$(uname)" == "Linux" ]]; then
        echo "Adding certificate to the trusted store"
        cp $CERT_FILE /usr/local/share/ca-certificates/
        update-ca-certificates
    elif [[ "$(uname)" == "Darwin" ]]; then
        echo "Adding certificate to the trusted store (Mac)"
        sudo security add-trusted-cert -d -r trustRoot -k /Library/Keychains/System.keychain $CERT_FILE
    else
        echo "Unsupported platform for adding certificates to the trusted store"
    fi
else
    echo "Self-signed certificates already exist"
fi

# Bring down any existing services
docker compose down

# Build the Docker image
docker compose build

# Start the services
docker compose up -d

# Wait for the MySQL container to be fully up
echo "Waiting for MySQL to be ready..."
until docker exec db mysqladmin ping -h "db" --silent; do
    echo "Waiting for MySQL to be up and running..."
    sleep 2
done

# Copy startup SQL to the container and load it.
MSQLC=$(docker ps --filter "name=db" --format "{{.ID}}")
docker cp ChugBotWithTestDBData.sql ${MSQLC}:/tmp

echo 'Loading database'
load() {
    docker exec -it ${MSQLC} bash -c '/usr/bin/mysql -u root -pdeveloper < /tmp/ChugBotWithTestDBData.sql'
}
load
while [ $? -ne 0 ]; do
    echo "Will retry database load..."
    sleep 2
    load
done
echo 'Database loaded'
