<?php
/**
 * Test 11 (J2C6 full-install): cart endpoints against real J2Commerce 6 tables.
 *
 * Requires:
 *   - J2Commerce 6 installed (jos_j2commerce_carts, jos_j2commerce_cartitems present)
 *   - Test user id=999 with cart seeded by docker-entrypoint.sh
 *   - Plugin enabled
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
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

function pass(string $name): void { global $passed; echo "✓ $name\n"; $passed++; }
function fail(string $name, string $d = ''): void { global $failed; echo "✗ $name" . ($d ? " — $d" : '') . "\n"; $failed++; }
function check(string $name, bool $ok, string $d = ''): void { $ok ? pass($name) : fail($name, $d); }

$db = Factory::getContainer()->get(DatabaseInterface::class);

// ── 1. Table presence ────────────────────────────────────────────────────────
echo "--- Table presence ---\n";
$db->setQuery('SHOW TABLES LIKE ' . $db->quote($db->getPrefix() . 'j2commerce_carts'));
check('jos_j2commerce_carts exists', $db->loadResult() !== null);

$db->setQuery('SHOW TABLES LIKE ' . $db->quote($db->getPrefix() . 'j2commerce_cartitems'));
check('jos_j2commerce_cartitems exists', $db->loadResult() !== null);

// ── 2. Detection methods ─────────────────────────────────────────────────────
echo "\n--- Detection methods ---\n";
$plugin = new \Advans\Plugin\Ajax\JoomlaAjaxForms\Extension\JoomlaAjaxForms(
    new Dispatcher(), ['params' => new Registry(['enable_j2store_cart' => 1])]
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
check('isJ2Commerce4() returns false (J2C6 tables present)', $is4 === false, var_export($is4, true));

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

// ── 4. IDOR: guest cannot delete user 999's item ──────────────────────────────
echo "\n--- IDOR: guest removeCartItem ---\n";
$token   = \Joomla\CMS\Session\Session::getFormToken();
$baseUrl = 'http://localhost/index.php?option=com_ajax&plugin=joomlaajaxforms&group=ajax&format=json';

$ch = curl_init($baseUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => http_build_query(['action' => 'removeCartItem', 'cart_item_id' => 1, $token => '1']),
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
    if (is_string($inner)) { $inner = json_decode($inner, true); }
    check('removeCartItem IDOR returns success=false',
        isset($inner['success']) && $inner['success'] === false,
        'Got: ' . json_encode($inner));
}

$db->setQuery('SELECT COUNT(*) FROM jos_j2commerce_cartitems WHERE j2commerce_cartitem_id = 1');
check('Cart item 1 not deleted by IDOR attempt', (int)$db->loadResult() === 1);

// ── 5. Authenticated removeCartItem via reflection ────────────────────────────
echo "\n--- removeCartItem via reflection (user 999) ---\n";
$app  = Factory::getApplication();
$user = Factory::getContainer()->get(\Joomla\CMS\User\UserFactoryInterface::class)->loadUserById(999);

if ($user && !$user->guest) {
    $appRc = new ReflectionClass($app);
    if ($appRc->hasProperty('identity')) {
        $p = $appRc->getProperty('identity');
        $p->setAccessible(true);
        $p->setValue($app, $user);
    }
    $app->input->set('cart_item_id', 2);

    $mRemove = $rc->getMethod('handleRemoveCartItem');
    $mRemove->setAccessible(true);
    try {
        $result = $mRemove->invoke($plugin);
        $data   = json_decode($result, true);
        check('removeCartItem returns valid JSON', $data !== null, "Raw: $result");
        check('removeCartItem success=true', ($data['success'] ?? false) === true, json_encode($data));
        check('removeCartItem returns data.cartCount', isset($data['data']['cartCount']), json_encode($data));
        if (isset($data['data']['cartCount'])) {
            check('cartCount decreased after remove', (int)$data['data']['cartCount'] < 3,
                "Got: {$data['data']['cartCount']}");
        }
    } catch (\Throwable $e) {
        fail('handleRemoveCartItem does not throw', $e->getMessage());
    }

    $db->setQuery('SELECT COUNT(*) FROM jos_j2commerce_cartitems WHERE j2commerce_cartitem_id = 2');
    check('Cart item 2 deleted from jos_j2commerce_cartitems', (int)$db->loadResult() === 0);
} else {
    fail('User 999 loadable for authenticated test', 'User not found or guest');
}

// ── Summary ───────────────────────────────────────────────────────────────────
echo "\n=== J2C6 Full-Install Cart Test Summary ===\n";
echo "Passed: $passed\n";
echo "Failed: $failed\n";
exit($failed > 0 ? 1 : 0);
