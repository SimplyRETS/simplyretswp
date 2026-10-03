#!/usr/bin/env bash

set -euo pipefail

db_name=${1:-wordpress_test}
db_user=${2:-root}
db_pass=${3:-}
db_host=${4:-localhost}
wp_version=${5:-latest}
skip_db_create=${6:-false}
installer=/tmp/install-wp-tests.sh

curl --fail --location --retry 3 --output "$installer" \
    https://raw.githubusercontent.com/wp-cli/scaffold-command/8ad932fce7be1112707c7ac119f6f828cca075d4/templates/install-wp-tests.sh

WP_INSTALL_TESTS_SKIP_UPDATE_CHECK=true \
    bash "$installer" "$db_name" "$db_user" "$db_pass" "$db_host" "$wp_version" "$skip_db_create"
