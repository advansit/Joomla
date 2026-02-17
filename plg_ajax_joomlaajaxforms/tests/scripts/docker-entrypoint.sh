#!/bin/bash
set -e

echo "=== Joomla AJAX Forms Test Environment Setup ==="
echo "Extension: ${EXTENSION_NAME:-unknown}"
echo "================================================="

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
    
    # Install extension using real Joomla Installer API
    echo "Installing extension via Joomla Installer..."
    cp /tmp/extension.zip /var/www/html/tmp/extension.zip
    chown www-data:www-data /var/www/html/tmp/extension.zip
    
    if php /tmp/install-extension.php /var/www/html/tmp/extension.zip; then
        echo "✅ Extension installed via Joomla Installer"
    else
        echo "❌ Extension installation FAILED via Joomla Installer"
        exit 1
    fi
    
    touch /var/www/html/health.txt
    echo "✅ Health file created"
fi

# Keep container running
wait $JOOMLA_PID
