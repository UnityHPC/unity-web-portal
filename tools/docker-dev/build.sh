#!/bin/bash
set -e
repo_dir="$(git rev-parse --show-toplevel)"
cd "$repo_dir"
source ./venv/bin/activate
output="$(cd ./tools && python ./generate_htpasswd_bootstrap-ldif.py)"
echo "$output"
eval "$output"
set -x
mv "$LDAP_BOOTSTRAP_LDIF_PATH" ./tools/docker-dev/identity/bootstrap.ldif
mv "$HTPASSWD_PATH" ./tools/docker-dev/web/htpasswd
mv "$SQL_BOOTSTRAP_USERS_PATH" ./tools/docker-dev/sql/bootstrap-users.sql
cd ./tools/docker-dev
docker-compose down
docker-compose build
docker image prune
