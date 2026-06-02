#!/bin/bash
set -e

JOOMLA_ROOT="/var/www/html"

(
    # Wait for Joomla auto-install to complete
    echo "[j2c4] Waiting for Joomla installation..."
    timeout 240 bash -c '
        until php -r "
            define(\"_JEXEC\",1);
            define(\"JPATH_BASE\",\"/var/www/html\");
            \$db=new mysqli(\"db\",\"joomla\",\"joomla\",\"joomla_test\");
            if(\$db->connect_error)exit(1);
            \$r=\$db->query(\"SELECT 1 FROM jos_extensions LIMIT 1\");
            exit(\$r&&\$r->num_rows>0?0:1);
        " 2>/dev/null; do
            sleep 5
        done
    '
    echo "[j2c4] Joomla ready."

    # Install J2Commerce 4
    echo "[j2c4] Installing J2Commerce 4..."
    php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
        --path=/tmp/j2commerce4.zip \
        --no-interaction
    echo "[j2c4] J2Commerce 4 installed."

    # Install our plugin
    echo "[j2c4] Installing plg_ajax_joomlaajaxforms..."
    php "$JOOMLA_ROOT/cli/joomla.php" extension:install \
        --path=/tmp/extension.zip \
        --no-interaction
    echo "[j2c4] Plugin installed."

    # Enable the plugin
    php "$JOOMLA_ROOT/cli/joomla.php" extension:enable \
        --folder=ajax --element=joomlaajaxforms \
        --no-interaction 2>/dev/null || true

    # Seed test cart data: one user (id=999), one cart, two cart items
    echo "[j2c4] Seeding cart data..."
    mysql -h db -u joomla -pjoomla joomla_test <<'SQL'
INSERT IGNORE INTO jos_users
    (id, name, username, email, password, block, sendEmail, registerDate, lastvisitDate, activation, params, lastResetTime, resetCount, otpKey, otep, requireReset, authProvider)
VALUES
    (999, 'Test Buyer', 'testbuyer', 'buyer@test.local',
     '$2y$10$abcdefghijklmnopqrstuuVGZzGzGzGzGzGzGzGzGzGzGzGzGzGzG',
     0, 0, NOW(), NOW(), '', '{}', NOW(), 0, '', '', 0, '');

INSERT IGNORE INTO jos_j2store_carts
    (j2store_cart_id, user_id, cart_type, created_on, modified_on, session_id)
VALUES
    (1, 999, 'cart', NOW(), NOW(), 'testsession999');

INSERT IGNORE INTO jos_j2store_cartitems
    (j2store_cartitem_id, cart_id, product_id, product_qty, product_options, product_type, created_on, modified_on)
VALUES
    (1, 1, 1, 2, '{}', 'simple', NOW(), NOW()),
    (2, 1, 2, 1, '{}', 'simple', NOW(), NOW());
SQL
    echo "[j2c4] Cart data seeded."

    # Signal ready
    touch "$JOOMLA_ROOT/health.txt"
    echo "[j2c4] Setup complete."
) &

exec /entrypoint.sh "$@"
