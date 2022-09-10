#!/bin/bash

cd "$(dirname "$0")"

docker-compose down
docker-compose build --no-cache
docker image prune