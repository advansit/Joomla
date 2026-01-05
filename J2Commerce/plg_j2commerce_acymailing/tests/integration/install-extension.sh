#!/bin/bash
# This script is executed during Joomla container initialization
# It installs the extension automatically

set -e

echo "Installing J2Commerce AcyMailing Plugin..."

# Wait for Joomla to be fully initialized
sleep 10

# Check if extension package exists
if [ ! -f /tmp/extension.zip ]; then
    echo "ERROR: Extension package not found at /tmp/extension.zip"
    exit 1
fi

# Extract extension to plugins directory
PLUGIN_DIR="/var/www/html/plugins/j2store/acymailing"
mkdir -p "$PLUGIN_DIR"

unzip -q /tmp/extension.zip -d "$PLUGIN_DIR"

echo "Extension files extracted to $PLUGIN_DIR"

# Set proper permissions
chown -R www-data:www-data "$PLUGIN_DIR"
chmod -R 755 "$PLUGIN_DIR"

# Insert plugin record into database (if not exists)
mysql -h mysql -u joomla -pjoomla_password joomla_db << EOF
INSERT IGNORE INTO j2store_extensions (name, type, element, folder, enabled, access, protected, manifest_cache, params, ordering)
VALUES (
    'J2Commerce - AcyMailing Integration',
    'plugin',
    'acymailing',
    'j2store',
    1,
    1,
    0,
    '{}',
    '{}',
    0
);
EOF

echo "Plugin installed and enabled successfully!"
echo "Installation complete: $(date)"
