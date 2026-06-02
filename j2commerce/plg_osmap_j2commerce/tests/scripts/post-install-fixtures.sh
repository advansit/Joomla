#!/bin/bash
# OSMap fixture setup for full-install (J2C4 and J2C6) test stacks.
# Called by shared/docker-entrypoint-j2c-base.sh after J2Commerce + extension install.
# $DB_PREFIX and $J2COMMERCE_VERSION are exported by the caller.
set -e

DB_HOST="${JOOMLA_DB_HOST:-mysql}"
DB_USER="${JOOMLA_DB_USER:-joomla}"
DB_PASS="${JOOMLA_DB_PASSWORD:-joomla_pass}"
DB_NAME="${JOOMLA_DB_NAME:-joomla_db}"
VER="${J2COMMERCE_VERSION:-4}"

# Select table/component names based on J2Commerce version
if [ "$VER" = "6" ]; then
    PRODUCTS_TABLE="${DB_PREFIX}j2commerce_products"
    VARIANTS_TABLE="${DB_PREFIX}j2commerce_variants"
    COM_ELEMENT="com_j2commerce"
    PRODUCT_ID_COL="j2commerce_product_id"
    VARIANT_ID_COL="j2commerce_variant_id"
else
    PRODUCTS_TABLE="${DB_PREFIX}j2store_products"
    VARIANTS_TABLE="${DB_PREFIX}j2store_variants"
    COM_ELEMENT="com_j2store"
    PRODUCT_ID_COL="j2store_product_id"
    VARIANT_ID_COL="j2store_variant_id"
fi

echo "[fixtures] Inserting OSMap fixtures (J2C$VER)..."

COM_CONTENT_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN \
    -e "SELECT extension_id FROM ${DB_PREFIX}extensions WHERE element='com_content' AND type='component' LIMIT 1;" 2>/dev/null || echo "0")
COM_J2_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN \
    -e "SELECT extension_id FROM ${DB_PREFIX}extensions WHERE element='$COM_ELEMENT' AND type='component' LIMIT 1;" 2>/dev/null || echo "0")

if [ "$COM_J2_ID" = "0" ]; then
    echo "ERROR: $COM_ELEMENT not found in extensions table"
    exit 1
fi

MAINMENU_ROOT_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN \
    -e "SELECT parent_id FROM ${DB_PREFIX}menu WHERE menutype='mainmenu' AND level=1 LIMIT 1;" 2>/dev/null)
if [ -z "$MAINMENU_ROOT_ID" ]; then
    MAINMENU_ROOT_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN \
        -e "SELECT id FROM ${DB_PREFIX}menu WHERE parent_id=0 LIMIT 1;" 2>/dev/null || echo "1")
fi

SHOP_LINK="index.php?option=${COM_ELEMENT}&view=products"

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" << EOSQL
SET @max_rgt = (SELECT COALESCE(MAX(rgt), 0) FROM ${DB_PREFIX}menu);

INSERT IGNORE INTO ${DB_PREFIX}menu
    (id, menutype, title, alias, path, link, type, published, parent_id, level, component_id, language, access, params, lft, rgt)
VALUES (
    9001, 'mainmenu', 'Shop', 'shop', 'shop',
    '${SHOP_LINK}',
    'component', 1, ${MAINMENU_ROOT_ID}, 1, ${COM_J2_ID}, '*', 1, '{}',
    @max_rgt + 1, @max_rgt + 6
);

INSERT IGNORE INTO ${DB_PREFIX}content
    (id, title, alias, introtext, \`fulltext\`, state, catid, created, modified, publish_up, language, access)
VALUES
    (9001, 'Test Product Alpha',    'test-product-alpha',    'Alpha',    '', 1, 2, NOW(), NOW(), NOW(), '*', 1),
    (9002, 'Test Product Beta',     'test-product-beta',     'Beta',     '', 1, 2, NOW(), NOW(), NOW(), '*', 1),
    (9003, 'Test Product Disabled', 'test-product-disabled', 'Disabled', '', 1, 2, NOW(), NOW(), NOW(), '*', 1),
    (9004, 'Test Product NoMenu',   'test-product-nomenu',   'NoMenu',   '', 1, 2, NOW(), NOW(), NOW(), '*', 1);

INSERT IGNORE INTO ${PRODUCTS_TABLE}
    (${PRODUCT_ID_COL}, product_source_id, product_source, visibility, enabled)
VALUES
    (9001, 9001, 'com_content', 1, 1),
    (9002, 9002, 'com_content', 1, 1),
    (9003, 9003, 'com_content', 1, 0),
    (9004, 9004, 'com_content', 1, 1);

INSERT IGNORE INTO ${VARIANTS_TABLE}
    (${VARIANT_ID_COL}, product_id, sku, price, is_master)
VALUES
    (9001, 9001, 'ALPHA-001', 49.00, 1),
    (9002, 9002, 'BETA-001',  79.00, 1),
    (9003, 9003, 'DISABLED',  19.00, 1),
    (9004, 9004, 'NOMENU',    29.00, 1);

INSERT IGNORE INTO ${DB_PREFIX}menu
    (id, menutype, title, alias, path, link, type, published, parent_id, level, component_id, language, access, params, lft, rgt)
VALUES
    (9002, 'mainmenu', 'Test Product Alpha', 'test-product-alpha', 'shop/test-product-alpha',
     'index.php?option=com_content&view=article&id=9001&Itemid=9002',
     'component', -2, 9001, 2, ${COM_CONTENT_ID}, '*', 1, '{}',
     @max_rgt + 2, @max_rgt + 3),
    (9003, 'mainmenu', 'Test Product Beta', 'test-product-beta', 'shop/test-product-beta',
     'index.php?option=com_content&view=article&id=9002&Itemid=9003',
     'component', -2, 9001, 2, ${COM_CONTENT_ID}, '*', 1, '{}',
     @max_rgt + 4, @max_rgt + 5);
EOSQL

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" << EOSQL
UPDATE ${DB_PREFIX}menu
SET rgt = (SELECT max_rgt FROM (SELECT MAX(rgt) + 1 AS max_rgt FROM ${DB_PREFIX}menu) AS t)
WHERE lft = 0;
EOSQL

MAINMENU_ID=$(mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" -sN \
    -e "SELECT id FROM ${DB_PREFIX}menu_types WHERE menutype='mainmenu' LIMIT 1;" 2>/dev/null || echo "0")

mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" << EOSQL
INSERT IGNORE INTO ${DB_PREFIX}osmap_sitemaps (id, name, params, is_default, published, created_on, links_count)
VALUES (1, 'Test Sitemap', '{}', 1, 1, NOW(), 0);

INSERT IGNORE INTO ${DB_PREFIX}osmap_sitemap_menus (sitemap_id, menutype_id, changefreq, priority, ordering)
VALUES (1, ${MAINMENU_ID}, 'weekly', 0.5, 1);
EOSQL

echo "[fixtures] OSMap fixtures inserted (J2C$VER, tables: $PRODUCTS_TABLE)."
