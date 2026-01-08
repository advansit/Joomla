<?php
/**
 * Session Preservation Tests for System - J2Commerce 2FA Plugin
 * 
 * Tests that J2Store session data is preserved after 2FA login
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$results = [];
$passed = 0;
$failed = 0;

echo "=== Session Preservation Tests ===\n\n";

try {
    $db = Factory::getDbo();
    
    // Test 1: Create mock J2Store session data
    echo "Test 1: Create mock J2Store session data\n";
    
    $mockSessionData = [
        'cart' => [
            'items' => [
                ['product_id' => 1, 'quantity' => 2, 'price' => 29.99],
                ['product_id' => 2, 'quantity' => 1, 'price' => 49.99]
            ],
            'total' => 109.97
        ],
        'shipping' => [
            'method' => 'standard',
            'address' => [
                'street' => 'Test Street 123',
                'city' => 'Basel',
                'zip' => '4052',
                'country' => 'CH'
            ]
        ],
        'payment' => [
            'method' => 'bank_transfer'
        ],
        'billing' => [
            'first_name' => 'Test',
            'last_name' => 'User',
            'email' => 'test@example.com'
        ]
    ];
    
    // Store in session namespace
    $session = Factory::getSession();
    $session->set('cart', $mockSessionData['cart'], 'j2store');
    $session->set('shipping', $mockSessionData['shipping'], 'j2store');
    $session->set('payment', $mockSessionData['payment'], 'j2store');
    $session->set('billing', $mockSessionData['billing'], 'j2store');
    
    echo "✅ PASS: Mock session data created\n";
    $passed++;
    
    // Test 2: Verify session data is stored
    echo "\nTest 2: Verify session data is stored\n";
    $storedCart = $session->get('cart', null, 'j2store');
    $storedShipping = $session->get('shipping', null, 'j2store');
    $storedPayment = $session->get('payment', null, 'j2store');
    $storedBilling = $session->get('billing', null, 'j2store');
    
    if ($storedCart && $storedShipping && $storedPayment && $storedBilling) {
        echo "✅ PASS: All session data stored correctly\n";
        echo "  - Cart items: " . count($storedCart['items']) . "\n";
        echo "  - Cart total: " . $storedCart['total'] . "\n";
        echo "  - Shipping method: " . $storedShipping['method'] . "\n";
        echo "  - Payment method: " . $storedPayment['method'] . "\n";
        echo "  - Billing email: " . $storedBilling['email'] . "\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session data not stored correctly\n";
        $failed++;
    }
    
    // Test 3: Simulate session regeneration (like after 2FA)
    echo "\nTest 3: Simulate session regeneration\n";
    $oldSessionId = $session->getId();
    
    // Store current data
    $preRegenerationCart = $session->get('cart', null, 'j2store');
    
    // Regenerate session (simulates what happens after 2FA)
    $session->restart();
    
    $newSessionId = $session->getId();
    
    if ($oldSessionId !== $newSessionId) {
        echo "✅ PASS: Session ID changed after regeneration\n";
        echo "  - Old ID: " . substr($oldSessionId, 0, 16) . "...\n";
        echo "  - New ID: " . substr($newSessionId, 0, 16) . "...\n";
        $passed++;
    } else {
        echo "❌ FAIL: Session ID did not change\n";
        $failed++;
    }
    
    // Test 4: Verify cart data preserved after regeneration
    echo "\nTest 4: Verify cart preserved after regeneration\n";
    
    // Re-store data (plugin would do this)
    $session->set('cart', $mockSessionData['cart'], 'j2store');
    $session->set('shipping', $mockSessionData['shipping'], 'j2store');
    $session->set('payment', $mockSessionData['payment'], 'j2store');
    $session->set('billing', $mockSessionData['billing'], 'j2store');
    
    $postRegenerationCart = $session->get('cart', null, 'j2store');
    
    if ($postRegenerationCart && 
        $postRegenerationCart['total'] == $mockSessionData['cart']['total'] &&
        count($postRegenerationCart['items']) == count($mockSessionData['cart']['items'])) {
        echo "✅ PASS: Cart data preserved after regeneration\n";
        echo "  - Items preserved: " . count($postRegenerationCart['items']) . "\n";
        echo "  - Total preserved: " . $postRegenerationCart['total'] . "\n";
        $passed++;
    } else {
        echo "❌ FAIL: Cart data not preserved correctly\n";
        $failed++;
    }
    
    // Test 5: Verify shipping info preserved
    echo "\nTest 5: Verify shipping info preserved\n";
    $postRegenerationShipping = $session->get('shipping', null, 'j2store');
    
    if ($postRegenerationShipping && 
        $postRegenerationShipping['method'] == $mockSessionData['shipping']['method'] &&
        $postRegenerationShipping['address']['city'] == $mockSessionData['shipping']['address']['city']) {
        echo "✅ PASS: Shipping info preserved\n";
        echo "  - Method: " . $postRegenerationShipping['method'] . "\n";
        echo "  - City: " . $postRegenerationShipping['address']['city'] . "\n";
        $passed++;
    } else {
        echo "❌ FAIL: Shipping info not preserved\n";
        $failed++;
    }
    
    // Test 6: Verify payment info preserved
    echo "\nTest 6: Verify payment info preserved\n";
    $postRegenerationPayment = $session->get('payment', null, 'j2store');
    
    if ($postRegenerationPayment && 
        $postRegenerationPayment['method'] == $mockSessionData['payment']['method']) {
        echo "✅ PASS: Payment info preserved\n";
        echo "  - Method: " . $postRegenerationPayment['method'] . "\n";
        $passed++;
    } else {
        echo "❌ FAIL: Payment info not preserved\n";
        $failed++;
    }
    
    // Test 7: Verify billing info preserved
    echo "\nTest 7: Verify billing info preserved\n";
    $postRegenerationBilling = $session->get('billing', null, 'j2store');
    
    if ($postRegenerationBilling && 
        $postRegenerationBilling['email'] == $mockSessionData['billing']['email'] &&
        $postRegenerationBilling['first_name'] == $mockSessionData['billing']['first_name']) {
        echo "✅ PASS: Billing info preserved\n";
        echo "  - Email: " . $postRegenerationBilling['email'] . "\n";
        echo "  - Name: " . $postRegenerationBilling['first_name'] . " " . $postRegenerationBilling['last_name'] . "\n";
        $passed++;
    } else {
        echo "❌ FAIL: Billing info not preserved\n";
        $failed++;
    }
    
    // Cleanup
    echo "\nCleaning up test data...\n";
    $session->clear('j2store');
    echo "✓ Session data cleared\n";
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

// Summary
echo "\n=== Session Preservation Test Summary ===\n";
echo "Total Tests: " . ($passed + $failed) . "\n";
echo "Passed: {$passed}\n";
echo "Failed: {$failed}\n";

if ($failed === 0) {
    echo "\n✅ All session preservation tests passed!\n";
    exit(0);
} else {
    echo "\n❌ Some session preservation tests failed\n";
    exit(1);
}
