#!/bin/bash

set -uo pipefail

docker compose down

docker build .

docker compose up -d

# Copy startup SQL to the container and load it.
MSQLC=`docker ps | grep mysql | awk '{print $1}'`
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
