#!/usr/bin/env bash
# Run J2Commerce 4 full-install cart tests inside the running container.
set -euo pipefail

CONTAINER="${CONTAINER_NAME:-plg_ajax_j2c4_test}"
SCRIPT="${1:-11-j2c4-cart}"
SCRIPT_FILE="/tests-j2c4/scripts/${SCRIPT}.php"

echo "=== Running $SCRIPT in $CONTAINER ==="
docker exec "$CONTAINER" php "$SCRIPT_FILE"
