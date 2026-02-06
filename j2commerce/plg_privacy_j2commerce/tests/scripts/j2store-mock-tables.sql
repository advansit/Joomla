-- J2Store/J2Commerce Mock Tables for Testing
-- These tables simulate the J2Store database structure for integration testing

-- Orders table
CREATE TABLE IF NOT EXISTS `#__j2store_orders` (
    `j2store_order_id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` varchar(50) DEFAULT NULL,
    `user_id` int(11) NOT NULL DEFAULT 0,
    `user_email` varchar(255) DEFAULT NULL,
    `billing_first_name` varchar(255) DEFAULT NULL,
    `billing_last_name` varchar(255) DEFAULT NULL,
    `billing_address_1` varchar(255) DEFAULT NULL,
    `billing_address_2` varchar(255) DEFAULT NULL,
    `billing_city` varchar(255) DEFAULT NULL,
    `billing_zip` varchar(50) DEFAULT NULL,
    `billing_country_id` int(11) DEFAULT NULL,
    `billing_zone_id` int(11) DEFAULT NULL,
    `billing_phone` varchar(50) DEFAULT NULL,
    `billing_company` varchar(255) DEFAULT NULL,
    `shipping_first_name` varchar(255) DEFAULT NULL,
    `shipping_last_name` varchar(255) DEFAULT NULL,
    `shipping_address_1` varchar(255) DEFAULT NULL,
    `shipping_address_2` varchar(255) DEFAULT NULL,
    `shipping_city` varchar(255) DEFAULT NULL,
    `shipping_zip` varchar(50) DEFAULT NULL,
    `shipping_country_id` int(11) DEFAULT NULL,
    `shipping_zone_id` int(11) DEFAULT NULL,
    `shipping_phone` varchar(50) DEFAULT NULL,
    `shipping_company` varchar(255) DEFAULT NULL,
    `order_total` decimal(15,5) DEFAULT 0.00000,
    `order_subtotal` decimal(15,5) DEFAULT 0.00000,
    `order_tax` decimal(15,5) DEFAULT 0.00000,
    `order_shipping` decimal(15,5) DEFAULT 0.00000,
    `order_discount` decimal(15,5) DEFAULT 0.00000,
    `order_state_id` int(11) DEFAULT 1,
    `created_on` datetime DEFAULT NULL,
    `modified_on` datetime DEFAULT NULL,
    `customer_note` text,
    `token` varchar(255) DEFAULT NULL,
    PRIMARY KEY (`j2store_order_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_created_on` (`created_on`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Order items table
CREATE TABLE IF NOT EXISTS `#__j2store_orderitems` (
    `j2store_orderitem_id` int(11) NOT NULL AUTO_INCREMENT,
    `order_id` int(11) NOT NULL,
    `product_id` int(11) DEFAULT NULL,
    `variant_id` int(11) DEFAULT NULL,
    `orderitem_sku` varchar(255) DEFAULT NULL,
    `orderitem_name` varchar(255) DEFAULT NULL,
    `orderitem_quantity` int(11) DEFAULT 1,
    `orderitem_price` decimal(15,5) DEFAULT 0.00000,
    `orderitem_final_price` decimal(15,5) DEFAULT 0.00000,
    `orderitem_tax` decimal(15,5) DEFAULT 0.00000,
    `orderitem_discount` decimal(15,5) DEFAULT 0.00000,
    `orderitem_options` text,
    PRIMARY KEY (`j2store_orderitem_id`),
    KEY `idx_order_id` (`order_id`),
    KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Addresses table
CREATE TABLE IF NOT EXISTS `#__j2store_addresses` (
    `j2store_address_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `first_name` varchar(255) DEFAULT NULL,
    `last_name` varchar(255) DEFAULT NULL,
    `address_1` varchar(255) DEFAULT NULL,
    `address_2` varchar(255) DEFAULT NULL,
    `city` varchar(255) DEFAULT NULL,
    `zip` varchar(50) DEFAULT NULL,
    `country_id` int(11) DEFAULT NULL,
    `zone_id` int(11) DEFAULT NULL,
    `phone` varchar(50) DEFAULT NULL,
    `email` varchar(255) DEFAULT NULL,
    `company` varchar(255) DEFAULT NULL,
    `type` varchar(50) DEFAULT 'billing',
    `is_default` tinyint(1) DEFAULT 0,
    PRIMARY KEY (`j2store_address_id`),
    KEY `idx_user_id` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Products table
CREATE TABLE IF NOT EXISTS `#__j2store_products` (
    `j2store_product_id` int(11) NOT NULL AUTO_INCREMENT,
    `product_source` varchar(50) DEFAULT 'com_content',
    `product_source_id` int(11) DEFAULT NULL,
    `product_type` varchar(50) DEFAULT 'simple',
    `visibility` tinyint(1) DEFAULT 1,
    `enabled` tinyint(1) DEFAULT 1,
    `created_on` datetime DEFAULT NULL,
    `modified_on` datetime DEFAULT NULL,
    `params` text,
    PRIMARY KEY (`j2store_product_id`),
    KEY `idx_source` (`product_source`, `product_source_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Variants table
CREATE TABLE IF NOT EXISTS `#__j2store_variants` (
    `j2store_variant_id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `is_master` tinyint(1) DEFAULT 0,
    `sku` varchar(255) DEFAULT NULL,
    `upc` varchar(255) DEFAULT NULL,
    `price` decimal(15,5) DEFAULT 0.00000,
    `pricing_calculator` varchar(50) DEFAULT 'standard',
    `shipping` tinyint(1) DEFAULT 1,
    `length` decimal(10,4) DEFAULT 0.0000,
    `width` decimal(10,4) DEFAULT 0.0000,
    `height` decimal(10,4) DEFAULT 0.0000,
    `weight` decimal(10,4) DEFAULT 0.0000,
    `manage_stock` tinyint(1) DEFAULT 0,
    `quantity_restriction` tinyint(1) DEFAULT 0,
    `min_sale_qty` int(11) DEFAULT 1,
    `max_sale_qty` int(11) DEFAULT 0,
    `notify_qty` int(11) DEFAULT 0,
    `availability` tinyint(1) DEFAULT 1,
    `params` text,
    PRIMARY KEY (`j2store_variant_id`),
    KEY `idx_product_id` (`product_id`),
    KEY `idx_sku` (`sku`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product quantities table
CREATE TABLE IF NOT EXISTS `#__j2store_productquantities` (
    `j2store_productquantity_id` int(11) NOT NULL AUTO_INCREMENT,
    `variant_id` int(11) NOT NULL,
    `quantity` int(11) DEFAULT 0,
    `on_hold` int(11) DEFAULT 0,
    `sold` int(11) DEFAULT 0,
    PRIMARY KEY (`j2store_productquantity_id`),
    KEY `idx_variant_id` (`variant_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart table
CREATE TABLE IF NOT EXISTS `#__j2store_carts` (
    `j2store_cart_id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT 0,
    `session_id` varchar(255) DEFAULT NULL,
    `cart_type` varchar(50) DEFAULT 'cart',
    `created_on` datetime DEFAULT NULL,
    `modified_on` datetime DEFAULT NULL,
    PRIMARY KEY (`j2store_cart_id`),
    KEY `idx_user_id` (`user_id`),
    KEY `idx_session_id` (`session_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Cart items table
CREATE TABLE IF NOT EXISTS `#__j2store_cartitems` (
    `j2store_cartitem_id` int(11) NOT NULL AUTO_INCREMENT,
    `cart_id` int(11) NOT NULL,
    `product_id` int(11) DEFAULT NULL,
    `variant_id` int(11) DEFAULT NULL,
    `quantity` int(11) DEFAULT 1,
    `product_options` text,
    PRIMARY KEY (`j2store_cartitem_id`),
    KEY `idx_cart_id` (`cart_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Product custom fields table
CREATE TABLE IF NOT EXISTS `#__j2store_product_customfields` (
    `j2store_product_customfield_id` int(11) NOT NULL AUTO_INCREMENT,
    `product_id` int(11) NOT NULL,
    `customfield_id` int(11) NOT NULL,
    `customfield_value` text,
    PRIMARY KEY (`j2store_product_customfield_id`),
    KEY `idx_product_id` (`product_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert test data
-- Test user (ID 100)
INSERT INTO `#__j2store_addresses` (`user_id`, `first_name`, `last_name`, `address_1`, `city`, `zip`, `country_id`, `email`, `type`, `is_default`)
VALUES 
(100, 'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 204, 'test@example.com', 'billing', 1),
(100, 'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 204, 'test@example.com', 'shipping', 0);

-- Test order (within retention period)
INSERT INTO `#__j2store_orders` (`order_id`, `user_id`, `user_email`, `billing_first_name`, `billing_last_name`, `billing_address_1`, `billing_city`, `billing_zip`, `order_total`, `order_state_id`, `created_on`)
VALUES 
('ORD-2024-001', 100, 'test@example.com', 'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 199.00, 1, DATE_SUB(NOW(), INTERVAL 1 YEAR));

-- Test order (outside retention period - older than 10 years)
INSERT INTO `#__j2store_orders` (`order_id`, `user_id`, `user_email`, `billing_first_name`, `billing_last_name`, `billing_address_1`, `billing_city`, `billing_zip`, `order_total`, `order_state_id`, `created_on`)
VALUES 
('ORD-2013-001', 100, 'test@example.com', 'Test', 'User', 'Teststrasse 1', 'Zürich', '8000', 99.00, 1, DATE_SUB(NOW(), INTERVAL 11 YEAR));

-- Test product
INSERT INTO `#__j2store_products` (`product_source`, `product_source_id`, `product_type`, `visibility`, `enabled`, `created_on`)
VALUES ('com_content', 1, 'simple', 1, 1, NOW());

-- Test variant
INSERT INTO `#__j2store_variants` (`product_id`, `is_master`, `sku`, `price`, `manage_stock`)
VALUES (1, 1, 'TEST-001', 99.00, 1);

-- Test quantity
INSERT INTO `#__j2store_productquantities` (`variant_id`, `quantity`, `on_hold`, `sold`)
VALUES (1, 100, 0, 5);

-- Test cart
INSERT INTO `#__j2store_carts` (`user_id`, `cart_type`, `created_on`)
VALUES (100, 'cart', NOW());

-- Test cart item
INSERT INTO `#__j2store_cartitems` (`cart_id`, `product_id`, `variant_id`, `quantity`)
VALUES (1, 1, 1, 2);
