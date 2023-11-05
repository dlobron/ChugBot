#!/bin/bash

docker compose down

docker build .

docker compose up
