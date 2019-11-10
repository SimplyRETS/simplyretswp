#!/usr/bin/env bash

TRAVIS_PHP_VERSION=${1-0}
WP_VERSION=${2-latest}

echo "[init-tests.sh] TRAVIS_PHP_VERSION=$TRAVIS_PHP_VERSION"
echo "[init-tests.sh] WP_VERSION=$WP_VERSION"

# PHP 7 is incompatible with WordPress v4 and lower so we force WordPress 5.0
if [[ $TRAVIS_PHP_VERSION == "7.1"* && $WP_VERSION == 4.0 ]]; then
    echo "[notice] PHP 7 is incompatible with WordPress v4 and lower. Forcing WordPress 5.0."
    WP_VERSION=5.0
else
    :
fi

echo "[init-tests.sh] Using WP_VERSION $WP_VERSION"

bash src/bin/install-wp-tests.sh wordpress_test root '' localhost $WP_VERSION
