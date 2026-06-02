<?php
/**
 * Test 11 (J2C4 full-install): cart endpoints against real J2Commerce 4 tables.
 *
 * Requires:
 *   - J2Commerce 4 installed (jos_j2store_carts, jos_j2store_cartitems present)
 *   - Test user id=999 with cart seeded by docker-entrypoint.sh
 *   - Plugin enabled
 *
 * Tests:
 *   - isJ2CommerceInstalled() returns true
 *   - isJ2Commerce4() returns true
 *   - getCartCount HTTP endpoint returns data.data.cartCount = 3 for user 999
 *   - removeCartItem HTTP endpoint deletes item, returns updated count
 *   - IDOR: removeCartItem for item owned by user 999 rejected when called as guest
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\User\UserHelper;
use Joomla\Database\DatabaseInterface;
use Joomla\Event\Dispatcher;
use Joomla\Registry\Registry;

JLoader::registerNamespace(
    'Advans\Plugin\Ajax\JoomlaAjaxForms',
    '/var/www/html/plugins/ajax/joomlaajaxforms/src',
    false, false, 'psr4'
);

$passed = 0;
$failed = 0;

function pass(string $name): void {
    global $passed;
    echo "✓ $name\n";
    $passed++;
}
function fail(string $name, string $detail = ''): void {
    global $failed;
    echo "✗ $name" . ($detail ? " — $detail" : '') . "\n";
    $failed++;
}
function check(string $name, bool $ok, string $detail = ''): void {
    $ok ? pass($name) : fail($name, $detail);
}

$db = Factory::getContainer()->get(DatabaseInterface::class);

// ── 1. Table presence ────────────────────────────────────────────────────────
echo "--- Table presence ---\n";
$db->setQuery('SHOW TABLES LIKE ' . $db->quote($db->getPrefix() . 'j2store_carts'));
check('jos_j2store_carts exists', $db->loadResult() !== null);

$db->setQuery('SHOW TABLES LIKE ' . $db->quote($db->getPrefix() . 'j2store_cartitems'));
check('jos_j2store_cartitems exists', $db->loadResult() !== null);

// ── 2. Detection methods ─────────────────────────────────────────────────────
echo "\n--- Detection methods ---\n";
$dispatcher = new Dispatcher();
$plugin = new \Advans\Plugin\Ajax\JoomlaAjaxForms\Extension\JoomlaAjaxForms(
    $dispatcher, ['params' => new Registry(['enable_j2store_cart' => 1])]
);
$plugin->setDatabase($db);

$rc = new ReflectionClass($plugin);

$mInstalled = $rc->getMethod('isJ2CommerceInstalled');
$mInstalled->setAccessible(true);
$installed = $mInstalled->invoke($plugin, $db);
check('isJ2CommerceInstalled() returns true', $installed === true, var_export($installed, true));

$mIs4 = $rc->getMethod('isJ2Commerce4');
$mIs4->setAccessible(true);
$is4 = $mIs4->invoke($plugin, $db);
check('isJ2Commerce4() returns true', $is4 === true, var_export($is4, true));

// ── 3. getCartCountForUser() with seeded data ─────────────────────────────────
echo "\n--- getCartCountForUser() with seeded data ---\n";
$mCount = $rc->getMethod('getCartCountForUser');
$mCount->setAccessible(true);

try {
    $count = $mCount->invoke($plugin, $db, 999);
    check('getCartCountForUser(999) returns int', is_int($count), gettype($count));
    check('getCartCountForUser(999) returns 3 (2+1 seeded items)', $count === 3, "Got: $count");
} catch (\Throwable $e) {
    fail('getCartCountForUser(999) does not throw', $e->getMessage());
}

// ── 4. HTTP: getCartCount endpoint ────────────────────────────────────────────
echo "\n--- HTTP: getCartCount (guest, expects 0) ---\n";

// Get a valid Joomla token for the request
$token = \Joomla\CMS\Session\Session::getFormToken();
$baseUrl = 'http://localhost/index.php?option=com_ajax&plugin=joomlaajaxforms&group=ajax&format=json';

$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query(['action' => 'getCartCount', $token => '1']),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

check('getCartCount HTTP 200', $code === 200, "Got HTTP $code");
$resp = json_decode($raw, true);
check('getCartCount response is valid JSON', $resp !== null, "Raw: " . substr($raw, 0, 200));
if ($resp !== null) {
    $cartCount = $resp['data'][0] ?? null;
    if (is_string($cartCount)) {
        $cartCount = json_decode($cartCount, true);
    }
    check('getCartCount guest returns cartCount=0',
        isset($cartCount['data']['cartCount']) && $cartCount['data']['cartCount'] === 0,
        'Got: ' . json_encode($cartCount));
}

// ── 5. HTTP: removeCartItem IDOR (guest cannot delete user 999's item) ────────
echo "\n--- HTTP: removeCartItem IDOR (guest) ---\n";

$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query([
        'action'       => 'removeCartItem',
        'cart_item_id' => 1,
        $token         => '1',
    ]),
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => 10,
    CURLOPT_HTTPHEADER     => ['Content-Type: application/x-www-form-urlencoded'],
]);
$raw  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
curl_close($ch);

check('removeCartItem IDOR HTTP 200', $code === 200, "Got HTTP $code");
$resp = json_decode($raw, true);
if ($resp !== null) {
    $inner = $resp['data'][0] ?? null;
    if (is_string($inner)) {
        $inner = json_decode($inner, true);
    }
    check('removeCartItem IDOR returns success=false',
        isset($inner['success']) && $inner['success'] === false,
        'Got: ' . json_encode($inner));
}

// Verify row still exists
$db->setQuery('SELECT COUNT(*) FROM jos_j2store_cartitems WHERE j2store_cartitem_id = 1');
$rowCount = (int) $db->loadResult();
check('Cart item 1 not deleted by IDOR attempt', $rowCount === 1, "Row count: $rowCount");

// ── 6. Direct removeCartItem via reflection (authenticated as user 999) ───────
echo "\n--- removeCartItem via reflection (user 999) ---\n";

// Seed a session for user 999 so getApplication()->getIdentity() returns them
$app = Factory::getApplication();
$user = Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(999);

if ($user && !$user->guest) {
    // Directly invoke handleRemoveCartItem with the DB seeded item
    $mRemove = $rc->getMethod('handleRemoveCartItem');
    $mRemove->setAccessible(true);

    // Inject user into application identity
    $appRc = new ReflectionClass($app);
    if ($appRc->hasProperty('identity')) {
        $identProp = $appRc->getProperty('identity');
        $identProp->setAccessible(true);
        $identProp->setValue($app, $user);
    }

    // Set cart_item_id in input
    $app->input->set('cart_item_id', 2);

    try {
        $result = $mRemove->invoke($plugin);
        $data   = json_decode($result, true);
        check('removeCartItem returns valid JSON', $data !== null, "Raw: $result");
        check('removeCartItem success=true', ($data['success'] ?? false) === true,
            'Got: ' . json_encode($data));
        check('removeCartItem returns data.cartCount',
            isset($data['data']['cartCount']),
            'Got: ' . json_encode($data));
        if (isset($data['data']['cartCount'])) {
            check('cartCount decreased after remove',
                (int)$data['data']['cartCount'] < 3,
                "Got: {$data['data']['cartCount']}");
        }
    } catch (\Throwable $e) {
        fail('handleRemoveCartItem does not throw', $e->getMessage());
    }

    // Verify row 2 deleted from DB
    $db->setQuery('SELECT COUNT(*) FROM jos_j2store_cartitems WHERE j2store_cartitem_id = 2');
    $remaining = (int) $db->loadResult();
    check('Cart item 2 deleted from jos_j2store_cartitems', $remaining === 0, "Row count: $remaining");
} else {
    fail('User 999 loadable for authenticated test', 'User not found or guest');
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo "\n=== J2C4 Full-Install Cart Test Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
exit($failed > 0 ? 1 : 0);
