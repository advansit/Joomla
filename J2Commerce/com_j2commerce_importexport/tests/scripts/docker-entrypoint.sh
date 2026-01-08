#!/bin/bash
set -e

# Run original Joomla entrypoint
/entrypoint.sh apache2-foreground &
JOOMLA_PID=$!

# Wait for Joomla to be ready
echo "Waiting for Joomla to initialize..."
sleep 15

# Check if Joomla is initialized
if [ -f /var/www/html/configuration.php ]; then
    echo "Joomla is initialized and ready"
fi

# Keep container running
wait $JOOMLA_PID
