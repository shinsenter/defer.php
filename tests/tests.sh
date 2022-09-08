#!/bin/sh

################################################################################

TEST_DIR="$(cd $(dirname $0) && pwd)"
ROOT_DIR="$(dirname $TEST_DIR)"
CONF_DIR="$ROOT_DIR/.config"

################################################################################

if [ -z "$(docker ps 2>/dev/null | grep defer-php-main)" ]; then
    docker compose -f $ROOT_DIR/.docker/docker-compose.yml up -d --build 2>&1
    docker exec -t defer-php-main  composer update -Wn 2>&1
    docker exec -t defer-php-fixer composer update -Wn 2>&1
    docker exec -t defer-php-fixer composer fixer      2>&1
    # docker exec -t defer-php-fixer composer rector     2>&1
    # docker exec -t defer-php-fixer composer phpstan    2>&1
fi

################################################################################

docker exec -it defer-php-main php tests/entrypoint.php
