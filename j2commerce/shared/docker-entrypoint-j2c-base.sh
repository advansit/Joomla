#!/bin/bash
# Shared full-install entrypoint for J2Commerce 4/6 test stacks.
#
# Environment variables (set by the caller):
#   J2COMMERCE_ZIP     — path to the J2Commerce package ZIP inside the container
#   J2COMMERCE_VERSION — 4 or 6 (used for log messages only)
#
# After installing J2Commerce, delegates to the extension's own
# docker-entrypoint.sh (which installs the extension under test).
set -e

JOOMLA_ROOT="/var/www/html"
J2C_ZIP="${J2COMMERCE_ZIP:-/tmp/j2commerce.zip}"
J2C_VER="${J2COMMERCE_VERSION:-?}"

echo "=== Full-Install Entrypoint: J2Commerce $J2C_VER ==="

# Start Joomla in the background (official image entrypoint handles DB setup)
/entrypoint.sh apache2-foreground &
JOOMLA_PID=$!

# Wait for Joomla auto-install to complete
echo "[j2c$J2C_VER] Waiting for Joomla installation..."
TIMEOUT=240
ELAPSED=0
while [ ! -f "$JOOMLA_ROOT/configuration.php" ] && [ $ELAPSED -lt $TIMEOUT ]; do
    sleep 5
    ELAPSED=$((ELAPSED + 5))
done

if [ ! -f "$JOOMLA_ROOT/configuration.php" ]; then
    echo "ERROR: Joomla did not initialize within ${TIMEOUT}s"
    exit 1
fi

# Extra wait for DB tables to be fully created
sleep 10

echo "[j2c$J2C_VER] Installing J2Commerce $J2C_VER from $J2C_ZIP..."
cp "$J2C_ZIP" "$JOOMLA_ROOT/tmp/j2commerce.zip"
if php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
       --path="$JOOMLA_ROOT/tmp/j2commerce.zip" \
       --no-interaction; then
    echo "[j2c$J2C_VER] J2Commerce $J2C_VER installed."
else
    echo "ERROR: J2Commerce $J2C_VER installation failed."
    exit 1
fi

echo "[j2c$J2C_VER] Installing extension under test..."
cp /tmp/extension.zip "$JOOMLA_ROOT/tmp/extension.zip"
if php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
       --path="$JOOMLA_ROOT/tmp/extension.zip" \
       --no-interaction; then
    echo "[j2c$J2C_VER] Extension installed."
else
    echo "ERROR: Extension installation failed."
    exit 1
fi

# Signal ready
touch "$JOOMLA_ROOT/health.txt"
echo "[j2c$J2C_VER] Setup complete."

# Keep the Joomla process running
wait $JOOMLA_PID
