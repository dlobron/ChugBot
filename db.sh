#!/bin/bash

# Get database instance
MSQLC=$(docker ps --filter "name=db" --format "{{.ID}}")

docker exec -it ${MSQLC} mysql -u root -pdeveloper camprama_chugbot_db