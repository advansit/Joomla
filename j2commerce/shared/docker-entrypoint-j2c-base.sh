#!/bin/bash
# Shared full-install entrypoint for J2Commerce 4/6 test stacks.
#
# Environment variables (set by the caller):
#   J2COMMERCE_ZIP     — path to the J2Commerce package ZIP inside the container
#   J2COMMERCE_VERSION — 4 or 6 (used for log messages only)
#
# After installing J2Commerce and the extension, calls /scripts/post-install-fixtures.sh
# if it exists (extension-specific fixture setup).
set -e

JOOMLA_ROOT="/var/www/html"
J2C_ZIP="${J2COMMERCE_ZIP:-/tmp/j2commerce.zip}"
J2C_VER="${J2COMMERCE_VERSION:-?}"

echo "=== Full-Install Entrypoint: J2Commerce $J2C_VER ==="

/entrypoint.sh apache2-foreground &
JOOMLA_PID=$!

echo "[j2c$J2C_VER] Waiting for Joomla installation..."
TIMEOUT=240
ELAPSED=0
while [ ! -f "$JOOMLA_ROOT/configuration.php" ] && [ $ELAPSED -lt $TIMEOUT ]; do
    sleep 5
    ELAPSED=$((ELAPSED + 5))
    echo "  Waiting... ($ELAPSED/${TIMEOUT}s)"
done

if [ ! -f "$JOOMLA_ROOT/configuration.php" ]; then
    echo "ERROR: Joomla did not initialize within ${TIMEOUT}s"
    exit 1
fi
echo "[j2c$J2C_VER] Joomla ready."
sleep 5

echo "[j2c$J2C_VER] Installing J2Commerce $J2C_VER..."
cp "$J2C_ZIP" "$JOOMLA_ROOT/tmp/j2commerce.zip"
if php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
       --path="$JOOMLA_ROOT/tmp/j2commerce.zip" \
       --no-interaction; then
    echo "[j2c$J2C_VER] J2Commerce $J2C_VER installed."
else
    echo "ERROR: J2Commerce $J2C_VER installation failed."
    exit 1
fi

# Install optional dependency (e.g. OSMap) before the extension under test
if [ -f /tmp/osmap.zip ]; then
    echo "[j2c$J2C_VER] Installing OSMap (required dependency)..."
    cp /tmp/osmap.zip "$JOOMLA_ROOT/tmp/osmap.zip"
    if php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
           --path="$JOOMLA_ROOT/tmp/osmap.zip" \
           --no-interaction; then
        echo "[j2c$J2C_VER] OSMap installed."
    else
        echo "ERROR: OSMap installation failed."
        exit 1
    fi
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

DB_PREFIX=$(php -r "require '$JOOMLA_ROOT/configuration.php'; echo (new JConfig)->dbprefix;" 2>/dev/null || echo "j_")
mysql -h "${JOOMLA_DB_HOST:-mysql}" \
      -u "${JOOMLA_DB_USER:-joomla}" \
      -p"${JOOMLA_DB_PASSWORD:-joomla_pass}" \
      "${JOOMLA_DB_NAME:-joomla_db}" \
      -e "UPDATE ${DB_PREFIX}extensions SET enabled = 1 WHERE enabled = 0 AND type = 'plugin';" 2>&1 \
    && echo "[j2c$J2C_VER] Plugins enabled." \
    || echo "[j2c$J2C_VER] WARNING: Could not enable plugins via DB."

if [ -f /scripts/post-install-fixtures.sh ]; then
    echo "[j2c$J2C_VER] Running post-install fixtures..."
    export DB_PREFIX
    export J2COMMERCE_VERSION="$J2C_VER"
    bash /scripts/post-install-fixtures.sh
    echo "[j2c$J2C_VER] Fixtures complete."
fi

echo "OK" > "$JOOMLA_ROOT/health.txt"
chown www-data:www-data "$JOOMLA_ROOT/health.txt" 2>/dev/null || true
echo "[j2c$J2C_VER] Setup complete."

wait $JOOMLA_PID
