#!/bin/bash
#
# Install SwissQRCode component manually
# Extracts files and runs SQL directly (bypasses Joomla installer issues)
#

set -e

CONTAINER="joomla_test"
PACKAGE_PATH="/tmp/com_swissqrcode.zip"

echo "=== Installing SwissQRCode Component ==="
echo ""

# Check if package exists
echo "Checking package..."
if ! docker exec "$CONTAINER" test -f "$PACKAGE_PATH"; then
    echo "❌ Package not found: $PACKAGE_PATH"
    exit 1
fi
echo "✅ Package found"

# Extract package
echo ""
echo "Extracting package..."
docker exec "$CONTAINER" bash -c "cd /tmp && unzip -q -o $PACKAGE_PATH"
echo "✅ Package extracted"

# Copy admin files
echo ""
echo "Installing admin files..."
docker exec "$CONTAINER" bash -c "
    mkdir -p /var/www/html/administrator/components/com_swissqrcode
    cp -r /tmp/admin/* /var/www/html/administrator/components/com_swissqrcode/
    chown -R www-data:www-data /var/www/html/administrator/components/com_swissqrcode
"
echo "✅ Admin files installed"

# Copy site files
echo ""
echo "Installing site files..."
docker exec "$CONTAINER" bash -c "
    mkdir -p /var/www/html/components/com_swissqrcode
    cp -r /tmp/site/* /var/www/html/components/com_swissqrcode/
    chown -R www-data:www-data /var/www/html/components/com_swissqrcode
"
echo "✅ Site files installed"

# Get database prefix
echo ""
echo "Getting database configuration..."
DB_PREFIX=$(docker exec "$CONTAINER" grep "dbprefix" /var/www/html/configuration.php | cut -d"'" -f2)
echo "Database prefix: $DB_PREFIX"

# Register extension
echo ""
echo "Registering extension in database..."
docker exec joomla_test_db mysql -ujoomla -pjoomla_secure_pass_2024 joomla_db <<EOF
-- Register admin component with namespace
INSERT INTO ${DB_PREFIX}extensions (package_id, name, type, element, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state, namespace)
VALUES (0, 'SwissQRCode', 'component', 'com_swissqrcode', '', 1, 1, 1, 0, 0, '', '{}', '', 0, NULL, 0, 0, 'Advans\\\\Component\\\\SwissQRCode')
ON DUPLICATE KEY UPDATE enabled=1, namespace='Advans\\\\Component\\\\SwissQRCode';

-- Register site component with namespace
INSERT INTO ${DB_PREFIX}extensions (package_id, name, type, element, folder, client_id, enabled, access, protected, locked, manifest_cache, params, custom_data, checked_out, checked_out_time, ordering, state, namespace)
VALUES (0, 'SwissQRCode', 'component', 'com_swissqrcode', '', 0, 1, 1, 0, 0, '', '{}', '', 0, NULL, 0, 0, 'Advans\\\\Component\\\\SwissQRCode')
ON DUPLICATE KEY UPDATE enabled=1, namespace='Advans\\\\Component\\\\SwissQRCode';
EOF
echo "✅ Extension registered"

# Run SQL installation
echo ""
echo "Creating database tables..."
docker exec "$CONTAINER" bash -c "
    cat /var/www/html/administrator/components/com_swissqrcode/sql/install.mysql.utf8.sql | \
    sed 's/#__/${DB_PREFIX}/g' | \
    mysql -h joomladb -ujoomla -pjoomla_secure_pass_2024 --skip-ssl joomla_db
"
echo "✅ Database tables created"

# Run installation script manually
echo ""
echo "Running post-installation tasks..."
docker exec "$CONTAINER" php -r "
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html/administrator');
require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

try {
    \$db = Factory::getDbo();
    
    // Get site default language
    \$query = \$db->getQuery(true)
        ->select(\$db->quoteName('lang_code'))
        ->from(\$db->quoteName('#__languages'))
        ->where(\$db->quoteName('published') . ' = 1')
        ->order(\$db->quoteName('ordering') . ' ASC');
    \$db->setQuery(\$query, 0, 1);
    \$defaultLang = \$db->loadResult() ?: '*';
    
    echo \"Default language: \$defaultLang\n\";
    
    // Create help article
    \$now = Factory::getDate()->toSql();
    \$article = new stdClass();
    \$article->title = 'SwissQRCode - Support';
    \$article->alias = 'swissqrcode';
    \$article->introtext = '<p>SwissQRCode Help Article</p>';
    \$article->fulltext = '';
    \$article->state = 1;
    \$article->catid = 2;
    \$article->created = \$now;
    \$article->created_by = 0;
    \$article->modified = \$now;
    \$article->language = \$defaultLang;
    \$article->access = 1;
    \$article->images = '{}';
    \$article->urls = '{}';
    \$article->attribs = '{}';
    \$article->metadata = '{}';
    \$article->metakey = '';
    \$article->metadesc = '';
    \$article->version = 1;
    \$article->ordering = 0;
    \$article->featured = 0;
    
    \$db->insertObject('#__content', \$article);
    \$articleId = \$db->insertid();
    echo \"Help article created: ID \$articleId\n\";
    
    // Get main menu type
    \$query = \$db->getQuery(true)
        ->select('menutype')
        ->from(\$db->quoteName('#__menu_types'))
        ->where(\$db->quoteName('client_id') . ' = 0')
        ->order(\$db->quoteName('id') . ' ASC');
    \$db->setQuery(\$query, 0, 1);
    \$menutype = \$db->loadResult() ?: 'mainmenu';
    
    // Create menu item for help article
    \$menu = new stdClass();
    \$menu->menutype = \$menutype;
    \$menu->title = 'SwissQRCode Support';
    \$menu->alias = 'swissqrcode';
    \$menu->path = 'swissqrcode';
    \$menu->link = 'index.php?option=com_content&view=article&id=' . \$articleId;
    \$menu->type = 'component';
    \$menu->published = 1;
    \$menu->parent_id = 1;
    \$menu->level = 1;
    \$menu->component_id = 22;
    \$menu->access = 1;
    \$menu->language = \$defaultLang;
    \$menu->client_id = 0;
    \$menu->home = 0;
    \$menu->params = '{}';
    \$menu->img = '';
    \$menu->template_style_id = 0;
    \$menu->browserNav = 0;
    
    \$db->insertObject('#__menu', \$menu);
    echo \"Help menu item created: ID \" . \$db->insertid() . \"\n\";
    
    // Get component ID
    \$query = \$db->getQuery(true)
        ->select('extension_id')
        ->from(\$db->quoteName('#__extensions'))
        ->where(\$db->quoteName('element') . ' = ' . \$db->quote('com_swissqrcode'))
        ->where(\$db->quoteName('type') . ' = ' . \$db->quote('component'))
        ->where(\$db->quoteName('client_id') . ' = 0');
    \$db->setQuery(\$query);
    \$componentId = \$db->loadResult();
    
    // Create menu item for license activation
    \$menu2 = new stdClass();
    \$menu2->menutype = \$menutype;
    \$menu2->title = 'License Activation';
    \$menu2->alias = 'license-activation';
    \$menu2->path = 'license-activation';
    \$menu2->link = 'index.php?option=com_swissqrcode&view=activate';
    \$menu2->type = 'component';
    \$menu2->published = 1;
    \$menu2->parent_id = 1;
    \$menu2->level = 1;
    \$menu2->component_id = \$componentId;
    \$menu2->access = 1;
    \$menu2->language = \$defaultLang;
    \$menu2->client_id = 0;
    \$menu2->home = 0;
    \$menu2->params = '{}';
    \$menu2->img = '';
    \$menu2->template_style_id = 0;
    \$menu2->browserNav = 0;
    
    \$db->insertObject('#__menu', \$menu2);
    echo \"Activation menu item created: ID \" . \$db->insertid() . \"\n\";
    
    echo \"✅ Post-installation complete\n\";
    
} catch (Exception \$e) {
    echo \"Error: \" . \$e->getMessage() . \"\n\";
    exit(1);
}
"

echo ""
echo "✅ Component installed successfully"
exit 0
