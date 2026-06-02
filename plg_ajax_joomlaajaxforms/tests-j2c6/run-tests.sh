#!/usr/bin/env bash
set -euo pipefail
CONTAINER="${CONTAINER_NAME:-plg_ajax_j2c6_test}"
SCRIPT="${1:-11-j2c6-cart}"
echo "=== Running $SCRIPT in $CONTAINER ==="
docker exec "$CONTAINER" php "/tests-j2c6/scripts/${SCRIPT}.php"
