<?php
/**
 * J2Commerce Mock Setup for Testing
 * Creates necessary database tables and structures for testing
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

try {
    $db = Factory::getDbo();
    
    echo "Setting up J2Commerce mock environment...\n";
    
    // Create j2store_orders table
    $query = "CREATE TABLE IF NOT EXISTS `#__j2store_orders` (
        `j2store_order_id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` VARCHAR(50) NOT NULL,
        `user_id` INT(11) DEFAULT NULL,
        `user_email` VARCHAR(255) NOT NULL,
        `billing_first_name` VARCHAR(100) DEFAULT NULL,
        `billing_last_name` VARCHAR(100) DEFAULT NULL,
        `order_state_id` INT(11) DEFAULT 1,
        `order_total` DECIMAL(10,2) DEFAULT 0.00,
        `created_date` DATETIME NOT NULL,
        `modified_date` DATETIME DEFAULT NULL,
        PRIMARY KEY (`j2store_order_id`),
        UNIQUE KEY `order_id` (`order_id`),
        KEY `user_email` (`user_email`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->setQuery($query);
    $db->execute();
    echo "✓ Created j2store_orders table\n";
    
    // Create j2store_order_items table
    $query = "CREATE TABLE IF NOT EXISTS `#__j2store_order_items` (
        `j2store_orderitem_id` INT(11) NOT NULL AUTO_INCREMENT,
        `order_id` VARCHAR(50) NOT NULL,
        `product_id` INT(11) NOT NULL,
        `product_name` VARCHAR(255) DEFAULT NULL,
        `product_sku` VARCHAR(100) DEFAULT NULL,
        `product_qty` INT(11) DEFAULT 1,
        `product_price` DECIMAL(10,2) DEFAULT 0.00,
        `orderitem_final_price` DECIMAL(10,2) DEFAULT 0.00,
        `created_date` DATETIME NOT NULL,
        PRIMARY KEY (`j2store_orderitem_id`),
        KEY `order_id` (`order_id`),
        KEY `product_id` (`product_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->setQuery($query);
    $db->execute();
    echo "✓ Created j2store_order_items table\n";
    
    // Create j2store_products table
    $query = "CREATE TABLE IF NOT EXISTS `#__j2store_products` (
        `j2store_product_id` INT(11) NOT NULL AUTO_INCREMENT,
        `product_id` INT(11) NOT NULL,
        `product_source` VARCHAR(50) DEFAULT 'com_j2store',
        `product_type` VARCHAR(50) DEFAULT 'simple',
        `sku` VARCHAR(100) DEFAULT NULL,
        `upc` VARCHAR(100) DEFAULT NULL,
        `price` DECIMAL(10,2) DEFAULT 0.00,
        `enabled` TINYINT(1) DEFAULT 1,
        `created_date` DATETIME NOT NULL,
        `modified_date` DATETIME DEFAULT NULL,
        PRIMARY KEY (`j2store_product_id`),
        UNIQUE KEY `product_id` (`product_id`),
        KEY `sku` (`sku`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    
    $db->setQuery($query);
    $db->execute();
    echo "✓ Created j2store_products table\n";
    
    // Insert test products - realistic J2Commerce Import/Export product catalog
    $products = [
        [1, 'SWQR-ADDON-1.0', 'J2Commerce Import/Export Add-In Basic', 49.00],
        [2, 'SWQR-ADDON-PRO-1.0', 'J2Commerce Import/Export Add-In Professional', 99.00],
        [3, 'SWQR-ENTERPRISE-1.0', 'J2Commerce Import/Export Enterprise License', 299.00],
        [4, 'SWQR-SUPPORT-1Y', 'J2Commerce Import/Export Support 1 Year', 149.00]
    ];
    
    foreach ($products as $product) {
        $query = "INSERT IGNORE INTO `#__j2store_products` 
            (`product_id`, `product_source`, `product_type`, `sku`, `price`, `enabled`, `created_date`) 
            VALUES 
            ({$product[0]}, 'com_j2store', 'simple', '{$product[1]}', {$product[3]}, 1, NOW())";
        
        $db->setQuery($query);
        $db->execute();
    }
    echo "✓ Inserted " . count($products) . " test products (SWQR-* SKUs)\n";
    
    // Register J2Commerce extension in extensions table
    $query = "INSERT IGNORE INTO `#__extensions` 
        (`package_id`, `name`, `type`, `element`, `folder`, `client_id`, `enabled`, `access`, `protected`, `locked`, `manifest_cache`, `params`, `custom_data`, `checked_out`, `checked_out_time`, `ordering`, `state`, `note`) 
        VALUES 
        (0, 'J2Commerce', 'component', 'com_j2store', '', 1, 1, 1, 0, 0, '', '{}', '', NULL, NULL, 0, 0, '')";
    
    $db->setQuery($query);
    $db->execute();
    echo "✓ Registered J2Commerce component\n";
    
    // Create test order and license for testing
    $testOrderId = 'TEST-ORDER-' . time();
    $query = "INSERT INTO `#__j2store_orders` 
        (`order_id`, `user_email`, `billing_first_name`, `billing_last_name`, `order_state_id`, `order_total`, `created_date`) 
        VALUES 
        ('$testOrderId', 'test@example.com', 'Test', 'User', 1, 49.00, NOW())";
    
    $db->setQuery($query);
    $db->execute();
    echo "✓ Created test order ($testOrderId)\n";
    
    // Create test order item
    $query = "INSERT INTO `#__j2store_order_items` 
        (`order_id`, `product_id`, `product_name`, `product_sku`, `product_qty`, `product_price`, `orderitem_final_price`, `created_date`) 
        VALUES 
        ('$testOrderId', 1, 'J2Commerce Import/Export Add-In', 'SWQR-ADDON-1.0', 1, 49.00, 49.00, NOW())";
    
    $db->setQuery($query);
    $db->execute();
    echo "✓ Created test order item\n";
    
    echo "\nJ2Commerce mock setup complete!\n";
    echo "Tables created: j2store_orders, j2store_order_items, j2store_products\n";
    echo "Products: 4 J2Commerce Import/Export products (SWQR-ADDON-1.0, SWQR-ADDON-PRO-1.0, SWQR-ENTERPRISE-1.0, SWQR-SUPPORT-1Y)\n";
    echo "Test order: $testOrderId\n";
    
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
