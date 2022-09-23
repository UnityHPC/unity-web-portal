@ECHO OFF
docker-compose down
docker-compose build --no-cache
docker image prune