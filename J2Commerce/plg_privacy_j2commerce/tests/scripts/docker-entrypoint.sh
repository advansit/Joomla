#!/bin/bash
set -e

# Run original Joomla entrypoint in background
/entrypoint.sh apache2-foreground &
JOOMLA_PID=$!

# Wait for Joomla to initialize
echo "Waiting for Joomla to initialize..."
sleep 15

# Check if Joomla is initialized
if [ -f /var/www/html/configuration.php ]; then
    echo "Joomla is initialized, installing extension..."
    
    # Wait a bit more for Apache to be fully ready
    sleep 5
    
    # Install extension via HTTP (like a real user would)
    php /usr/local/bin/install-via-http.php || echo "Extension installation failed, but continuing..."
    
    echo "Extension installation complete"
else
    echo "⚠️  Joomla not initialized yet, extension will not be installed"
fi

# Keep container running
wait $JOOMLA_PID
