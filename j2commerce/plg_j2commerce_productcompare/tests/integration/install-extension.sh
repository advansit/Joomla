#!/bin/bash
set -e

echo "Installing extension..."
sleep 10

if [ ! -f /tmp/extension.zip ]; then
    echo "ERROR: Extension package not found"
    exit 1
fi

PLUGIN_DIR="/var/www/html/plugins/j2store/productcompare"
mkdir -p "$PLUGIN_DIR"
unzip -q /tmp/extension.zip -d "$PLUGIN_DIR"
chown -R www-data:www-data "$PLUGIN_DIR"
chmod -R 755 "$PLUGIN_DIR"

mysql -h mysql -u joomla -pjoomla_password joomla_db << 'EOSQL'
INSERT IGNORE INTO j2store_extensions (name, type, element, folder, enabled, access, protected, manifest_cache, params, ordering)
VALUES ('plg j2commerce productcompare', 'plugin', 'productcompare', 'j2store', 1, 1, 0, '{}', '{}', 0);
EOSQL

echo "Plugin installed successfully!"
