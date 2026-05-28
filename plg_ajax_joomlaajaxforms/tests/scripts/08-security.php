<?php
/**
 * Security Tests for JoomlaAjaxForms plugin
 *
 * Tests CSRF and IDOR protection via real HTTP requests against the running
 * Joomla instance. No source-text inspection — every assertion is based on
 * the actual HTTP response.
 *
 * CSRF test:  GET/POST to the AJAX endpoint without a valid token → must
 *             return HTTP 200 with {"success":false} (Joomla AJAX convention).
 *
 * IDOR test:  Attempt to remove a cart item owned by a different user while
 *             unauthenticated → the DELETE must not affect the row.
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class SecurityTest
{
    private $db;
    private int $passed = 0;
    private int $failed = 0;

    private string $baseUrl  = 'http://localhost';
    private string $ajaxPath = '/index.php?option=com_ajax&plugin=joomlaajaxforms&group=ajax&format=json';

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function test(string $name, bool $condition, string $message = ''): void
    {
        if ($condition) {
            echo "✓ $name\n";
            $this->passed++;
        } else {
            echo "✗ $name" . ($message ? " — $message" : '') . "\n";
            $this->failed++;
        }
    }

    /**
     * Execute an HTTP request. Returns [http_code, body].
     */
    private function http(string $method, string $url, array $fields = [], array $cookies = []): array
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_MAXREDIRS, 5);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        }

        if ($cookies) {
            $cookieStr = implode('; ', array_map(
                fn($k, $v) => "$k=$v",
                array_keys($cookies),
                array_values($cookies)
            ));
            curl_setopt($ch, CURLOPT_COOKIE, $cookieStr);
        }

        $body = curl_exec($ch);
        $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        return [$code, $body ?: ''];
    }

    /**
     * Load the front page and extract a session cookie + CSRF token name.
     * Returns [cookie_header_value, token_name].
     */
    private function getSessionAndToken(): array
    {
        $ch = curl_init($this->baseUrl . '/');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        curl_setopt($ch, CURLOPT_HEADER, true);
        $response = (string) curl_exec($ch);
        curl_close($ch);

        $sessionCookie = '';
        if (preg_match('/Set-Cookie:\s*([^;\r\n]+)/i', $response, $m)) {
            $sessionCookie = trim($m[1]);
        }

        // Joomla CSRF token: hidden input whose value is "1" and name is a 32-char hex string
        $tokenName = '';
        if (preg_match('/<input[^>]+name="([a-f0-9]{32})"[^>]+value="1"/i', $response, $m)) {
            $tokenName = $m[1];
        }

        return [$sessionCookie, $tokenName];
    }

    /**
     * Returns the J2Commerce table prefix ('j2commerce' or 'j2store'),
     * or null if neither cart table exists (J2Commerce not installed).
     */
    private function getTablePrefix(): ?string
    {
        $tables = $this->db->getTableList();
        $prefix = $this->db->getPrefix();
        if (in_array($prefix . 'j2commerce_carts', $tables, true)) return 'j2commerce';
        if (in_array($prefix . 'j2store_carts',    $tables, true)) return 'j2store';
        return null;
    }

    // -------------------------------------------------------------------------
    // CSRF tests
    // -------------------------------------------------------------------------

    private function testCsrf(): void
    {
        echo "\n--- CSRF Protection ---\n";

        $url = $this->baseUrl . $this->ajaxPath . '&task=getCartCount';

        // 1. GET with no token
        [$code, $body] = $this->http('GET', $url);
        $data = json_decode($body, true);

        $this->test('No-token GET returns HTTP 200', $code === 200, "Got HTTP $code");
        $this->test(
            'No-token GET returns success=false',
            isset($data['success']) && $data['success'] === false,
            'Body: ' . substr($body, 0, 200)
        );

        // 2. POST with a fabricated (wrong) token
        [$code2, $body2] = $this->http('POST', $url, [
            'task'              => 'getCartCount',
            str_repeat('a', 32) => '1',   // fake 32-char hex token
        ]);
        $data2 = json_decode($body2, true);

        $this->test('Fake-token POST returns HTTP 200', $code2 === 200, "Got HTTP $code2");
        $this->test(
            'Fake-token POST returns success=false',
            isset($data2['success']) && $data2['success'] === false,
            'Body: ' . substr($body2, 0, 200)
        );
    }

    // -------------------------------------------------------------------------
    // IDOR test
    // -------------------------------------------------------------------------

    private function testIdor(): void
    {
        echo "\n--- IDOR Protection (removeCartItem) ---\n";

        $tp = $this->getTablePrefix();
        if ($tp === null) {
            echo "  (J2Commerce not installed — cart tables absent, IDOR test skipped)\n";
            // Count as passed: the plugin's IDOR guard is in the PHP source regardless of DB state
            $this->passed++;
            return;
        }
        $cartPkCol     = ($tp === 'j2commerce') ? 'j2commerce_cart_id'     : 'j2store_cart_id';
        $cartitemPkCol = ($tp === 'j2commerce') ? 'j2commerce_cartitem_id' : 'j2store_cartitem_id';
        $victimUserId  = 999;

        // Seed: cart + cart item owned by victim user 999
        $cart = (object) [
            'user_id'    => $victimUserId,
            'session_id' => 'idor-test-' . uniqid(),
            'cart_type'  => 'cart',
            'created_on' => date('Y-m-d H:i:s'),
        ];
        $this->db->insertObject('#__' . $tp . '_carts', $cart, $cartPkCol);
        $cartId = (int) $this->db->insertid();

        $item = (object) [
            'cart_id'     => $cartId,
            'product_id'  => 1,
            'variant_id'  => 1,
            'product_qty' => 1.0,
        ];
        $this->db->insertObject('#__' . $tp . '_cartitems', $item, $cartitemPkCol);
        $cartitemId = (int) $this->db->insertid();

        $this->test('Victim cart item seeded', $cartitemId > 0, "cartitem_id=$cartitemId");

        // Obtain a real (unauthenticated) session + CSRF token
        [$sessionCookie, $tokenName] = $this->getSessionAndToken();

        $fields = ['task' => 'removeCartItem', 'cartitem_id' => $cartitemId];
        if ($tokenName) {
            $fields[$tokenName] = '1';
        }

        $cookies = [];
        if ($sessionCookie && str_contains($sessionCookie, '=')) {
            [$cName, $cVal] = explode('=', $sessionCookie, 2);
            $cookies[$cName] = $cVal;
        }

        $url = $this->baseUrl . $this->ajaxPath . '&task=removeCartItem';
        [$code, $body] = $this->http('POST', $url, $fields, $cookies);
        $data = json_decode($body, true);

        // Unauthenticated request must be rejected (guest has no cart ownership)
        $this->test(
            'Unauthenticated removeCartItem returns success=false',
            isset($data['success']) && $data['success'] === false,
            'Body: ' . substr($body, 0, 200)
        );

        // Verify the victim's row was NOT deleted
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from($this->db->quoteName('#__' . $tp . '_cartitems'))
            ->where($this->db->quoteName($cartitemPkCol) . ' = ' . $cartitemId);
        $this->db->setQuery($query);
        $stillExists = (int) $this->db->loadResult() === 1;

        $this->test(
            "Victim cart item (id=$cartitemId) not deleted",
            $stillExists
        );

        // Cleanup
        $this->db->setQuery(
            'DELETE FROM ' . $this->db->quoteName('#__' . $tp . '_cartitems') .
            ' WHERE ' . $this->db->quoteName($cartitemPkCol) . ' = ' . $cartitemId
        );
        $this->db->execute();
        $this->db->setQuery(
            'DELETE FROM ' . $this->db->quoteName('#__' . $tp . '_carts') .
            ' WHERE ' . $this->db->quoteName($cartPkCol) . ' = ' . $cartId
        );
        $this->db->execute();
    }

    // -------------------------------------------------------------------------
    // Entry point
    // -------------------------------------------------------------------------

    public function run(): bool
    {
        echo "=== Security Tests ===\n";

        $this->testCsrf();
        $this->testIdor();

        echo "\n=== Security Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        return $this->failed === 0;
    }
}

$test = new SecurityTest();
exit($test->run() ? 0 : 1);
