#!/bin/bash
# Privacy plugin fixture setup for full-install (J2C4 and J2C6) test stacks.
# $DB_PREFIX and $J2COMMERCE_VERSION are exported by the caller.
set -e

DB_HOST="${JOOMLA_DB_HOST:-mysql}"
DB_USER="${JOOMLA_DB_USER:-joomla}"
DB_PASS="${JOOMLA_DB_PASSWORD:-joomla_pass}"
DB_NAME="${JOOMLA_DB_NAME:-joomla_db}"
VER="${J2COMMERCE_VERSION:-4}"

if [ "$VER" = "6" ]; then
    CARTS_TABLE="${DB_PREFIX}j2commerce_carts"
    CARTITEMS_TABLE="${DB_PREFIX}j2commerce_cartitems"
    ORDERS_TABLE="${DB_PREFIX}j2commerce_orders"
    ORDERITEMS_TABLE="${DB_PREFIX}j2commerce_orderitems"
    ORDERINFOS_TABLE="${DB_PREFIX}j2commerce_orderinfos"
    ADDRESSES_TABLE="${DB_PREFIX}j2commerce_addresses"
    CART_ID_COL="j2commerce_cart_id"
    CARTITEM_ID_COL="j2commerce_cartitem_id"
else
    CARTS_TABLE="${DB_PREFIX}j2store_carts"
    CARTITEMS_TABLE="${DB_PREFIX}j2store_cartitems"
    ORDERS_TABLE="${DB_PREFIX}j2store_orders"
    ORDERITEMS_TABLE="${DB_PREFIX}j2store_orderitems"
    ORDERINFOS_TABLE="${DB_PREFIX}j2store_orderinfos"
    ADDRESSES_TABLE="${DB_PREFIX}j2store_addresses"
    CART_ID_COL="j2store_cart_id"
    CARTITEM_ID_COL="j2store_cartitem_id"
fi

echo "[fixtures] Inserting privacy fixtures (J2C$VER)..."

# User and address fixtures are schema-identical for J2C4 and J2C6
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null <<EOSQL
INSERT IGNORE INTO ${DB_PREFIX}users (id, name, username, email, password, block, sendEmail, registerDate, params)
VALUES (100, 'Test User', 'testuser', 'test@example.com', '', 0, 0, NOW(), '{}');

INSERT IGNORE INTO ${DB_PREFIX}user_usergroup_map (user_id, group_id) VALUES (100, 2);

INSERT INTO ${ADDRESSES_TABLE} (user_id, first_name, last_name, email, address_1, city, zip, country_id, type)
VALUES
    (100, 'Test', 'User', 'test@example.com', 'Teststrasse 1', 'Zürich', '8000', 204, 'billing'),
    (100, 'Test', 'User', 'test@example.com', 'Teststrasse 1', 'Zürich', '8000', 204, 'shipping');
EOSQL

if [ "$VER" = "6" ]; then
    # J2Commerce 6: orders/orderinfos/orderitems have additional NOT NULL columns without defaults
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null <<EOSQL
INSERT INTO ${ORDERS_TABLE}
    (order_id, cart_id, invoice_prefix, invoice_number, token,
     user_id, user_email,
     order_total, order_subtotal, order_tax, order_shipping, order_shipping_tax,
     order_discount, order_credit, order_surcharge,
     orderpayment_type, transaction_id, transaction_status, transaction_details,
     currency_id, currency_code, currency_value,
     ip_address, is_shippable, is_including_tax,
     customer_note, customer_language, customer_group,
     order_state_id, order_state, created_on)
VALUES
    ('ORD-2024-001', 0, 'INV-', 1, 'tok001',
     100, 'test@example.com',
     199.00000, 180.00000, 19.00000, 0.00000, 0.00000,
     0.00000, 0.00000, 0.00000,
     '', '', '', '',
     1, 'CHF', 1.00000,
     '127.0.0.1', 0, 0,
     '', '', '',
     1, 'Confirmed', DATE_SUB(NOW(), INTERVAL 1 YEAR)),
    ('ORD-2013-001', 0, 'INV-', 2, 'tok002',
     100, 'test@example.com',
      99.00000,  90.00000,  9.00000, 0.00000, 0.00000,
     0.00000, 0.00000, 0.00000,
     '', '', '', '',
     1, 'CHF', 1.00000,
     '127.0.0.1', 0, 0,
     '', '', '',
     1, 'Confirmed', DATE_SUB(NOW(), INTERVAL 11 YEAR));

INSERT INTO ${ORDERINFOS_TABLE}
    (order_id,
     billing_first_name, billing_last_name, billing_address_1, billing_city,
     billing_zip, billing_country_id,
     shipping_first_name, shipping_last_name, shipping_address_1, shipping_city,
     shipping_zip, shipping_country_id,
     all_billing, all_shipping, all_payment)
VALUES
    ('ORD-2024-001',
     'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 204,
     'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 204,
     '{}', '{}', '{}'),
    ('ORD-2013-001',
     'Test', 'User', 'Alte Strasse 5', 'Bern', '3000', 204,
     'Test', 'User', 'Alte Strasse 5', 'Bern', '3000', 204,
     '{}', '{}', '{}');

INSERT INTO ${ORDERITEMS_TABLE}
    (order_id, cart_id, cartitem_id,
     product_id, product_type, variant_id, vendor_id,
     orderitem_sku, orderitem_name, orderitem_attributes,
     orderitem_quantity, orderitem_taxprofile_id,
     orderitem_per_item_tax, orderitem_tax, orderitem_discount, orderitem_discount_tax,
     orderitem_price, orderitem_option_price, orderitem_finalprice,
     orderitem_finalprice_with_tax, orderitem_finalprice_without_tax,
     orderitem_params, created_on, created_by,
     orderitem_weight, orderitem_weight_total)
VALUES
    ('ORD-2024-001', 0, 0,
     1, 'simple', 1, 0,
     'PROD-001', 'Test Product', '',
     '1', 0,
     0.00000, 0.00000, 0.00000, 0.00000,
     180.00000, 0.00000, 199.00000,
     199.00000, 180.00000,
     '', NOW(), 0,
     '0', '0'),
    ('ORD-2013-001', 0, 0,
     2, 'simple', 2, 0,
     'PROD-002', 'Old Product', '',
     '1', 0,
     0.00000, 0.00000, 0.00000, 0.00000,
      90.00000, 0.00000,  99.00000,
      99.00000,  90.00000,
     '', NOW(), 0,
     '0', '0');
EOSQL
else
    # J2Commerce 4 schema
    mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null <<EOSQL
INSERT INTO ${ORDERS_TABLE} (order_id, user_id, user_email, order_total, order_subtotal, order_tax, order_shipping, order_discount, order_state_id, order_state, currency_code, currency_value, created_on)
VALUES
    ('ORD-2024-001', 100, 'test@example.com', 199.00000, 180.00000, 19.00000, 0.00000, 0.00000, 1, 'Confirmed', 'CHF', 1.00000000, DATE_SUB(NOW(), INTERVAL 1 YEAR)),
    ('ORD-2013-001', 100, 'test@example.com',  99.00000,  90.00000,  9.00000, 0.00000, 0.00000, 1, 'Confirmed', 'CHF', 1.00000000, DATE_SUB(NOW(), INTERVAL 11 YEAR));

INSERT INTO ${ORDERINFOS_TABLE} (order_id, billing_first_name, billing_last_name, billing_address_1, billing_city, billing_zip, billing_country_id, billing_phone_1, shipping_first_name, shipping_last_name, shipping_address_1, shipping_city, shipping_zip, shipping_country_id)
VALUES
    ('ORD-2024-001', 'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 204, '+41 44 123 45 67', 'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 204),
    ('ORD-2013-001', 'Test', 'User', 'Alte Strasse 5', 'Bern',   '3000', 204, '+41 31 987 65 43', 'Test', 'User', 'Alte Strasse 5', 'Bern',   '3000', 204);

INSERT INTO ${ORDERITEMS_TABLE} (order_id, product_id, variant_id, orderitem_sku, orderitem_name, orderitem_quantity, orderitem_price, orderitem_finalprice)
VALUES
    ('ORD-2024-001', 1, 1, 'PROD-001', 'Test Product', '1', 180.00000, 199.00000),
    ('ORD-2013-001', 2, 2, 'PROD-002', 'Old Product',  '1',  90.00000,  99.00000);
EOSQL
fi

# Cart fixtures: cart_analytics column exists in both J2C4 and J2C6 schemas
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null <<EOSQL
INSERT INTO ${CARTS_TABLE} (user_id, session_id, cart_type, created_on, modified_on, customer_ip, cart_params, cart_browser, cart_analytics)
VALUES (100, 'test-session-123', 'cart', NOW(), NOW(), '127.0.0.1', '{}', '', '');

INSERT INTO ${CARTITEMS_TABLE} (cart_id, product_id, variant_id, vendor_id, product_type, cartitem_params, product_qty, product_options)
VALUES (LAST_INSERT_ID(), 1, 1, 0, 'simple', '{}', 2.0000, '{}');
EOSQL

# AcyMailing test schema
mysql -h "$DB_HOST" -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" 2>/dev/null <<EOACYM
CREATE TABLE IF NOT EXISTS ${DB_PREFIX}acym_configuration (
    id    INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name  VARCHAR(255) NOT NULL,
    value TEXT,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ${DB_PREFIX}acym_list (
    id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
    name         VARCHAR(255) NOT NULL,
    display_name VARCHAR(255) DEFAULT NULL,
    visible      TINYINT(1)   NOT NULL DEFAULT 1,
    PRIMARY KEY (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ${DB_PREFIX}acym_user (
    id            INT UNSIGNED NOT NULL AUTO_INCREMENT,
    email         VARCHAR(255) NOT NULL,
    name          VARCHAR(255) DEFAULT NULL,
    confirmed     TINYINT(1)   NOT NULL DEFAULT 0,
    creation_date DATETIME     DEFAULT NULL,
    PRIMARY KEY (id),
    UNIQUE KEY uq_acym_user_email (email)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS ${DB_PREFIX}acym_user_has_list (
    user_id           INT UNSIGNED NOT NULL,
    list_id           INT UNSIGNED NOT NULL,
    status            TINYINT(1)   NOT NULL DEFAULT 1,
    subscription_date DATETIME     DEFAULT NULL,
    unsubscribe_date  DATETIME     DEFAULT NULL,
    PRIMARY KEY (user_id, list_id),
    CONSTRAINT fk_acym_uhl_user FOREIGN KEY (user_id) REFERENCES ${DB_PREFIX}acym_user (id),
    CONSTRAINT fk_acym_uhl_list FOREIGN KEY (list_id) REFERENCES ${DB_PREFIX}acym_list (id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO ${DB_PREFIX}acym_list (id, name, display_name, visible) VALUES (1, 'newsletter', 'Newsletter', 1);
INSERT IGNORE INTO ${DB_PREFIX}acym_user (email, name, confirmed, creation_date) VALUES ('acym-test@example.com', 'AcyMailing Test User', 1, NOW());
INSERT IGNORE INTO ${DB_PREFIX}acym_user_has_list (user_id, list_id, status, subscription_date)
    SELECT id, 1, 1, NOW() FROM ${DB_PREFIX}acym_user WHERE email = 'acym-test@example.com';
EOACYM

echo "[fixtures] Privacy fixtures inserted (J2C$VER)."
