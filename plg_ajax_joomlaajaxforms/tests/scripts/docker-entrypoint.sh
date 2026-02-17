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
    echo "Installing extension via Joomla CLI..."
    cp /tmp/extension.zip /var/www/html/tmp/extension.zip
    if php /var/www/html/cli/joomla.php extension:install --path=/var/www/html/tmp/extension.zip; then
        echo "✅ Extension installed via Joomla CLI"
    else
        echo "❌ Extension installation FAILED via Joomla CLI"
        exit 1
    fi
    
    touch /var/www/html/health.txt
    echo "✅ Health file created"
fi

# Keep container running
wait $JOOMLA_PID
