#!/bin/bash
set -e

echo "=== J2Commerce Import/Export Test Environment Setup ==="
echo "Extension: ${EXTENSION_NAME:-J2Commerce Import/Export}"
echo "======================================================="

# Wait for MySQL to be ready
echo "Waiting for MySQL..."
until mysql -h mysql -u joomla -pjoomla_pass -e "SELECT 1" &>/dev/null; do
    sleep 2
done
echo "✅ MySQL is ready"

# Run original Joomla entrypoint in background
/entrypoint.sh apache2-foreground &
JOOMLA_PID=$!

# Wait for Joomla files to be extracted
echo "Waiting for Joomla files..."
sleep 10

# Check if Joomla needs installation
if [ ! -f /var/www/html/configuration.php ]; then
    echo "Installing Joomla via CLI..."
    
    # Wait for files to be fully extracted
    until [ -f /var/www/html/installation/joomla.php ] || [ -f /var/www/html/cli/joomla.php ]; do
        sleep 2
    done
    
    # Use Joomla CLI installer (Joomla 4+)
    if [ -f /var/www/html/cli/joomla.php ]; then
        php /var/www/html/cli/joomla.php site:install \
            --db-host=mysql \
            --db-user=joomla \
            --db-pass=joomla_pass \
            --db-name=joomla_db \
            --db-prefix=j_ \
            --db-type=mysql \
            --site-name="Test Site" \
            --admin-user="admin" \
            --admin-username="admin" \
            --admin-password="Admin123!" \
            --admin-email="admin@test.local" \
            --no-interaction 2>&1 || echo "CLI install attempted"
    fi
    
    # Fallback: Create configuration.php manually if CLI failed
    if [ ! -f /var/www/html/configuration.php ]; then
        echo "Creating configuration.php manually..."
        cat > /var/www/html/configuration.php << 'EOFCONFIG'
<?php
class JConfig {
    public $offline = false;
    public $offline_message = 'This site is down for maintenance.';
    public $display_offline_message = 1;
    public $offline_image = '';
    public $sitename = 'Test Site';
    public $editor = 'tinymce';
    public $captcha = '0';
    public $list_limit = 20;
    public $access = 1;
    public $debug = false;
    public $debug_lang = false;
    public $debug_lang_const = true;
    public $dbtype = 'mysqli';
    public $host = 'mysql';
    public $user = 'joomla';
    public $password = 'joomla_pass';
    public $db = 'joomla_db';
    public $dbprefix = 'j_';
    public $dbencryption = 0;
    public $dbsslverifyservercert = false;
    public $dbsslkey = '';
    public $dbsslcert = '';
    public $dbsslca = '';
    public $dbsslcipher = '';
    public $force_ssl = 0;
    public $live_site = '';
    public $secret = 'testsecret123456';
    public $gzip = false;
    public $error_reporting = 'default';
    public $helpurl = 'https://help.joomla.org/proxy';
    public $tmp_path = '/var/www/html/tmp';
    public $log_path = '/var/www/html/administrator/logs';
    public $lifetime = 15;
    public $session_handler = 'database';
    public $shared_session = false;
    public $session_metadata = true;
}
EOFCONFIG
        chown www-data:www-data /var/www/html/configuration.php
        
        # Import Joomla schema
        echo "Importing Joomla database schema..."
        if [ -f /var/www/html/installation/sql/mysql/base.sql ]; then
            mysql -h mysql -u joomla -pjoomla_pass joomla_db < /var/www/html/installation/sql/mysql/base.sql 2>/dev/null || true
        fi
        if [ -f /var/www/html/installation/sql/mysql/extensions.sql ]; then
            mysql -h mysql -u joomla -pjoomla_pass joomla_db < /var/www/html/installation/sql/mysql/extensions.sql 2>/dev/null || true
        fi
        if [ -f /var/www/html/installation/sql/mysql/supports.sql ]; then
            mysql -h mysql -u joomla -pjoomla_pass joomla_db < /var/www/html/installation/sql/mysql/supports.sql 2>/dev/null || true
        fi
        
        # Create admin user
        ADMIN_PASS_HASH=$(php -r "echo password_hash('Admin123!', PASSWORD_BCRYPT);")
        mysql -h mysql -u joomla -pjoomla_pass joomla_db -e "
            INSERT INTO j_users (id, name, username, email, password, block, sendEmail, registerDate, params) 
            VALUES (42, 'Super User', 'admin', 'admin@test.local', '$ADMIN_PASS_HASH', 0, 1, NOW(), '{}')
            ON DUPLICATE KEY UPDATE password='$ADMIN_PASS_HASH';
            INSERT INTO j_user_usergroup_map (user_id, group_id) VALUES (42, 8) ON DUPLICATE KEY UPDATE group_id=8;
        " 2>/dev/null || true
    fi
    
    echo "✅ Joomla installation complete"
fi

# Wait for configuration.php
echo "Waiting for Joomla configuration..."
until [ -f /var/www/html/configuration.php ]; do
    sleep 2
done

# Install extension
echo "Installing extension..."
sleep 5

cd /tmp
unzip -q -o extension.zip -d extracted 2>/dev/null || true

# Get table prefix from configuration
TABLE_PREFIX=$(grep "public \$dbprefix" /var/www/html/configuration.php | grep -oP "'\K[^']+" | head -1)
TABLE_PREFIX=${TABLE_PREFIX:-j_}

# Determine extension type and paths from manifest
MANIFEST=$(find extracted -name "*.xml" -type f ! -name "phpunit.xml" | head -1)
if [ -f "$MANIFEST" ]; then
    TYPE=$(grep -oP 'type="\K[^"]+' "$MANIFEST" | head -1)
    ELEMENT=$(grep -oP '<element>\K[^<]+' "$MANIFEST" | head -1)
    NAME=$(grep -oP '<name>\K[^<]+' "$MANIFEST" | head -1)
    
    # Fallback to filename if <element> tag not found
    if [ -z "$ELEMENT" ]; then
        ELEMENT=$(basename "$MANIFEST" .xml | sed 's/^plg_[^_]*_//' | sed 's/^com_//')
    fi
    
    echo "Extension: $NAME (type: $TYPE, element: $ELEMENT)"
    
    if [ "$TYPE" = "plugin" ]; then
        FOLDER=$(grep -oP 'group="\K[^"]+' "$MANIFEST" | head -1)
        INSTALL_PATH="/var/www/html/plugins/$FOLDER/$ELEMENT"
        echo "Installing plugin to: $INSTALL_PATH"
        
        mkdir -p "$INSTALL_PATH"
        cp -r extracted/* "$INSTALL_PATH/"
        chown -R www-data:www-data "$INSTALL_PATH"
        
        # Register plugin in database
        mysql -h mysql -u joomla -pjoomla_pass joomla_db -e "
            INSERT INTO ${TABLE_PREFIX}extensions 
            (name, type, element, folder, client_id, enabled, access, manifest_cache, params) 
            VALUES ('$NAME', 'plugin', '$ELEMENT', '$FOLDER', 0, 1, 1, '', '{}')
            ON DUPLICATE KEY UPDATE enabled=1;
        " 2>/dev/null && echo "✅ Plugin registered" || echo "⚠️ Plugin registration skipped"
        
    elif [ "$TYPE" = "component" ]; then
        INSTALL_PATH="/var/www/html/administrator/components/com_$ELEMENT"
        echo "Installing component to: $INSTALL_PATH"
        
        mkdir -p "$INSTALL_PATH"
        cp -r extracted/administrator/components/com_$ELEMENT/* "$INSTALL_PATH/" 2>/dev/null || cp -r extracted/* "$INSTALL_PATH/"
        chown -R www-data:www-data "$INSTALL_PATH"
        
        # Register component in database
        mysql -h mysql -u joomla -pjoomla_pass joomla_db -e "
            INSERT INTO ${TABLE_PREFIX}extensions 
            (name, type, element, folder, client_id, enabled, access, manifest_cache, params) 
            VALUES ('$NAME', 'component', 'com_$ELEMENT', '', 1, 1, 1, '', '{}')
            ON DUPLICATE KEY UPDATE enabled=1;
        " 2>/dev/null && echo "✅ Component registered" || echo "⚠️ Component registration skipped"
    fi
    
    echo "✅ Extension installation complete"
else
    echo "❌ No manifest found"
fi

# Create a simple health check file
echo "OK" > /var/www/html/health.txt
chown www-data:www-data /var/www/html/health.txt

echo "✅ Container ready"

# Keep container running
wait $JOOMLA_PID
