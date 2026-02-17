#!/bin/bash
set -e

echo "=== J2Commerce Test Environment Setup ==="
echo "Extension: ${EXTENSION_NAME:-unknown}"
echo "=========================================="

# Run original Joomla entrypoint
/entrypoint.sh apache2-foreground &
JOOMLA_PID=$!

# Wait for Joomla to be ready
echo "Waiting for Joomla to initialize..."
sleep 15

# Check if Joomla is initialized
if [ -f /var/www/html/configuration.php ]; then
    echo "Joomla is initialized, installing extension..."
    
    # Wait a bit more for Apache to be fully ready
    sleep 5
    
    # Install extension using Joomla CLI
    echo "Installing extension via Joomla CLI..."
    cp /tmp/extension.zip /var/www/html/tmp/extension.zip
    if php /var/www/html/cli/joomla.php extension:install --path=/var/www/html/tmp/extension.zip; then
        echo "✅ Extension installed via Joomla CLI"
    else
        echo "❌ Extension installation FAILED via Joomla CLI"
        exit 1
    fi
    
    # Enable all newly installed extensions (plugins are disabled by default)
    echo "Enabling installed extensions..."
    mysql -h "${JOOMLA_DB_HOST:-mysql}" -u "${JOOMLA_DB_USER:-joomla}" -p"${JOOMLA_DB_PASSWORD:-joomla_pass}" "${JOOMLA_DB_NAME:-joomla_db}" \
        -e "UPDATE ${TABLE_PREFIX:-j_}extensions SET enabled = 1 WHERE enabled = 0 AND type IN ('plugin', 'component', 'module') AND extension_id > 10000;" 2>/dev/null \
        && echo "✅ Extensions enabled" \
        || echo "⚠️ Could not enable extensions via DB (non-fatal)"
    
    # Create health file to signal readiness
    echo "OK" > /var/www/html/health.txt
    chown www-data:www-data /var/www/html/health.txt 2>/dev/null || true
    echo "✅ Health file created"
fi

# Keep container running
wait $JOOMLA_PID
