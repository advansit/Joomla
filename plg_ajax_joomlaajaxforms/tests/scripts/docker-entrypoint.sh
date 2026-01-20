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
    
    # Extract extension
    cd /tmp
    unzip -q extension.zip -d extracted
    
    # Get table prefix from configuration
    TABLE_PREFIX=$(grep "public \$dbprefix" /var/www/html/configuration.php | grep -oP "'\K[^']+" | tr -d '\n\r;')
    
    # Determine extension type and paths from manifest
    MANIFEST=$(ls extracted/*.xml 2>/dev/null | grep -v phpunit | head -1)
    if [ -f "$MANIFEST" ]; then
        TYPE=$(grep -oP 'type="\K[^"]+' "$MANIFEST" | head -1)
        ELEMENT=$(grep -oP '<element>\K[^<]+' "$MANIFEST" | head -1)
        
        # Fallback to filename if <element> tag not found
        if [ -z "$ELEMENT" ]; then
            ELEMENT=$(basename "$MANIFEST" .xml)
        fi
        
        if [ "$TYPE" = "plugin" ]; then
            FOLDER=$(grep -oP 'group="\K[^"]+' "$MANIFEST" | head -1)
            INSTALL_PATH="/var/www/html/plugins/$FOLDER/$ELEMENT"
            echo "Installing plugin: $ELEMENT (folder: $FOLDER)"
        elif [ "$TYPE" = "component" ]; then
            INSTALL_PATH="/var/www/html/administrator/components/com_$ELEMENT"
            echo "Installing component: com_$ELEMENT"
        fi
        
        # Copy files
        mkdir -p "$INSTALL_PATH"
        cp -r extracted/* "$INSTALL_PATH/"
        chown -R www-data:www-data "$INSTALL_PATH"
        
        # Register in database
        if [ "$TYPE" = "plugin" ]; then
            # Get name from manifest
            NAME=$(grep -oP '<name>\K[^<]+' "$MANIFEST" | head -1)
            # Install plugin as ENABLED (enabled=1) for testing
            SQL="INSERT INTO ${TABLE_PREFIX}extensions (package_id, name, type, element, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state, note) VALUES (0, '$NAME', 'plugin', '$ELEMENT', '$FOLDER', 0, 1, 1, 0, 0, '', '{\"enable_reset\":1,\"enable_remind\":1}', '', 0, NULL, 0, 0, '');"
            timeout 10 mysql -h mysql -u joomla -pjoomla_pass joomla_db -e "$SQL" 2>&1 >/dev/null && echo "✅ Extension registered in database (enabled)" || echo "❌ Failed to register extension"
        elif [ "$TYPE" = "component" ]; then
            NAME=$(grep -oP '<name>\K[^<]+' "$MANIFEST" | head -1)
            SQL="INSERT INTO ${TABLE_PREFIX}extensions (package_id, name, type, element, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state, note) VALUES (0, '$NAME', 'component', 'com_$ELEMENT', '', 1, 1, 1, 0, 0, '', '{}', '', 0, NULL, 0, 0, '');"
            timeout 10 mysql -h mysql -u joomla -pjoomla_pass joomla_db -e "$SQL" 2>&1 >/dev/null && echo "✅ Extension registered in database" || echo "❌ Failed to register extension"
        fi
        
        echo "✅ Extension installation complete"
    fi
fi

# Keep container running
wait $JOOMLA_PID
