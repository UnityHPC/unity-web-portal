#!/bin/bash
set -e
repo_dir="$(git rev-parse --show-toplevel)"
cd "$repo_dir/tools/docker-dev"
output="$(python ./generate-user-bootstrap-files.py)"
echo "$output"
eval "$output"
set -x
mv "$LDAP_BOOTSTRAP_LDIF_PATH" ./identity/bootstrap.ldif
mv "$HTPASSWD_PATH" ./web/htpasswd
mv "$SQL_BOOTSTRAP_USERS_PATH" ./sql/bootstrap-users.sql
docker-compose down
docker-compose build
docker image prune
