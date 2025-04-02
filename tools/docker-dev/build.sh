#!/bin/bash
set -e
repo_dir="$(git rev-parse --show-toplevel)"
cd "$repo_dir"
source ./venv/bin/activate
output="$(python ./tools/generate_htpasswd_bootstrap-ldif.py)"
echo "$output"
eval "$output"
set -x
mv "$LDAP_BOOTSTRAP_LDIF_PATH" ./tools/docker-dev/identity/bootstrap.ldif
mv "$HTPASSWD_PATH" ./tools/docker-dev/web/htpasswd
cd ./tools/docker-dev
docker-compose down
docker-compose build
docker image prune
