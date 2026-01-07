#!/bin/bash
set -e

echo "=== J2Commerce Test Environment Setup ==="
echo "Extension: ${EXTENSION_NAME:-unknown}"
echo "=========================================="

# Run original Joomla entrypoint in background
/entrypoint.sh apache2-foreground &
JOOMLA_PID=$!

# Wait for Joomla to initialize
echo "Waiting for Joomla to initialize..."
sleep 20

# Wait for Joomla to be accessible
echo "Waiting for Joomla HTTP to be ready..."
timeout 60 bash -c 'until curl -sf http://localhost > /dev/null 2>&1; do sleep 2; done' || {
    echo "❌ Joomla did not become ready in time"
    exit 1
}

echo "✅ Joomla is accessible"

# Check if Joomla is installed
if [ -f /var/www/html/configuration.php ]; then
    echo "✅ Joomla is installed"
    
    # Install extension via HTTP
    if [ -f /tmp/extension.zip ]; then
        echo ""
        echo "Installing extension via HTTP..."
        if php /usr/local/bin/install-extension-http.php; then
            echo "✅ Extension installation complete"
        else
            echo "❌ Extension installation failed"
            echo "Container will continue running for debugging"
        fi
    else
        echo "⚠️  No extension package found at /tmp/extension.zip"
    fi
    
    echo ""
    echo "=== Test Environment Ready ==="
    echo "Installation successful"
else
    echo "⚠️  Joomla configuration.php not found - installation may have failed"
fi

# Keep container running
wait $JOOMLA_PID
