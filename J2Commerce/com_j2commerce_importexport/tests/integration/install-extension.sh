#!/bin/bash
set -e

echo "Installing component..."
sleep 10

if [ ! -f /tmp/extension.zip ]; then
    echo "ERROR: Extension package not found"
    exit 1
fi

COMPONENT_DIR="/var/www/html/administrator/components/com_j2commerce_importexport"
mkdir -p "$COMPONENT_DIR"
unzip -q /tmp/extension.zip -d "$COMPONENT_DIR"
chown -R www-data:www-data "$COMPONENT_DIR"
chmod -R 755 "$COMPONENT_DIR"

mysql -h mysql -u joomla -pjoomla_password joomla_db << 'EOSQL'
INSERT IGNORE INTO j2store_extensions (name, type, element, enabled, access, protected, manifest_cache, params, ordering)
VALUES ('com j2commerce importexport', 'component', 'com_j2commerce_importexport', 1, 1, 0, '{}', '{}', 0);
EOSQL

echo "Component installed successfully!"
