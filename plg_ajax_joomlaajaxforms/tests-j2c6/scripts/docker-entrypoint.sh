#!/bin/bash
set -e

JOOMLA_ROOT="/var/www/html"

(
    echo "[j2c6] Waiting for Joomla installation..."
    timeout 240 bash -c '
        until php -r "
            \$db=new mysqli(\"db\",\"joomla\",\"joomla\",\"joomla_test\");
            if(\$db->connect_error)exit(1);
            \$r=\$db->query(\"SELECT 1 FROM jos_extensions LIMIT 1\");
            exit(\$r&&\$r->num_rows>0?0:1);
        " 2>/dev/null; do
            sleep 5
        done
    '
    echo "[j2c6] Joomla ready."

    echo "[j2c6] Installing J2Commerce 6..."
    php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
        --path=/tmp/j2commerce6.zip \
        --no-interaction
    echo "[j2c6] J2Commerce 6 installed."

    echo "[j2c6] Installing plg_ajax_joomlaajaxforms..."
    php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
        --path=/tmp/extension.zip \
        --no-interaction
    php "$JOOMLA_ROOT/cli/joomla.php" extension:enable \
        --folder=ajax --element=joomlaajaxforms \
        --no-interaction 2>/dev/null || true
    echo "[j2c6] Plugin installed."

    echo "[j2c6] Seeding cart data..."
    mysql -h db -u joomla -pjoomla joomla_test <<'SQL'
INSERT IGNORE INTO jos_users
    (id, name, username, email, password, block, sendEmail, registerDate, lastvisitDate, activation, params, lastResetTime, resetCount, otpKey, otep, requireReset, authProvider)
VALUES
    (999, 'Test Buyer', 'testbuyer', 'buyer@test.local',
     '$2y$10$abcdefghijklmnopqrstuuVGZzGzGzGzGzGzGzGzGzGzGzGzGzGzG',
     0, 0, NOW(), NOW(), '', '{}', NOW(), 0, '', '', 0, '');

-- j2commerce_carts: created_on, modified_on, customer_ip, cart_params, cart_browser, cart_voucher, cart_coupon, cart_analytics are NOT NULL
INSERT IGNORE INTO jos_j2commerce_carts
    (j2commerce_cart_id, user_id, session_id, cart_type, created_on, modified_on, customer_ip, cart_params, cart_browser, cart_voucher, cart_coupon, cart_analytics)
VALUES
    (1, 999, 'testsession999', 'cart', NOW(), NOW(), '127.0.0.1', '{}', '', '', '', '');

-- j2commerce_cartitems: no created_on/modified_on; variant_id, vendor_id, cartitem_params are NOT NULL
INSERT IGNORE INTO jos_j2commerce_cartitems
    (j2commerce_cartitem_id, cart_id, product_id, variant_id, vendor_id, product_type, cartitem_params, product_qty, product_options)
VALUES
    (1, 1, 1, 0, 0, 'simple', '{}', 2, '{}'),
    (2, 1, 2, 0, 0, 'simple', '{}', 1, '{}');
SQL
    echo "[j2c6] Cart data seeded."

    echo "OK" > "$JOOMLA_ROOT/health.txt"
    echo "[j2c6] Setup complete."
) &

exec /entrypoint.sh "$@"
