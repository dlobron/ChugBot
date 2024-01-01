#!/bin/bash

docker compose down

docker build .

docker compose up -d

MSQLC=`docker ps | grep mysql | awk '{print $1}'`

docker cp ChugBotWithData.sql ${MSQLC}:/tmp