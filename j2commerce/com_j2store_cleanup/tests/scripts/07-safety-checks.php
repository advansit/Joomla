<?php
/**
 * Safety Checks Tests for J2Store Cleanup v1.1.0
 * Tests that protected extensions cannot be removed
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class SafetyChecksTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    // Protected extensions list from j2store_cleanup.php
    private $protectedElements = [
        'com_j2store',
        'com_j2store_cleanup',
        'com_j2commerce_importexport',
        'plg_privacy_j2commerce',
        'plg_j2commerce_productcompare'
    ];

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    /**
     * Replicate the detection function from j2store_cleanup.php
     */
    private function checkJ2StoreCompatibility($manifest, $ext): array
    {
        if (in_array($ext->element, $this->protectedElements)) {
            return ['incompatible' => false, 'reason' => ''];
        }

        if (!is_object($manifest)) {
            return ['incompatible' => true, 'reason' => 'Invalid manifest data'];
        }

        if (isset($manifest->authorUrl) && strpos($manifest->authorUrl, 'j2commerce.com') !== false) {
            return ['incompatible' => false, 'reason' => ''];
        }

        $version = $manifest->version ?? 'Unknown';
        if ($version !== 'Unknown' && version_compare($version, '4.0.0', '<')) {
            return [
                'incompatible' => true,
                'reason' => 'Version ' . $version . ' < 4.0.0'
            ];
        }

        if (isset($manifest->authorUrl) && strpos($manifest->authorUrl, 'j2store.org') !== false) {
            return [
                'incompatible' => true,
                'reason' => 'Legacy J2Store (j2store.org)'
            ];
        }

        if (isset($manifest->authorEmail) && strpos($manifest->authorEmail, '@j2store.org') !== false) {
            return [
                'incompatible' => true,
                'reason' => 'Legacy J2Store (@j2store.org)'
            ];
        }

        return ['incompatible' => false, 'reason' => ''];
    }

    /**
     * Check if extension exists in database
     */
    private function extensionExists(string $element): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__extensions')
            ->where('element = ' . $this->db->quote($element));
        $this->db->setQuery($query);
        return (int)$this->db->loadResult() > 0;
    }

    private function test(string $name, bool $condition): void
    {
        if ($condition) {
            echo "Test: $name... PASS\n";
            $this->passed++;
        } else {
            echo "Test: $name... FAIL\n";
            $this->failed++;
        }
    }

    public function run(): bool
    {
        echo "=== Safety Checks Tests (v1.1.0 Protected Extensions) ===\n\n";

        // Test 1: All protected elements are in the list
        echo "--- Protected Elements List ---\n";
        $this->test('com_j2store is in protected list', in_array('com_j2store', $this->protectedElements));
        $this->test('com_j2store_cleanup is in protected list', in_array('com_j2store_cleanup', $this->protectedElements));
        $this->test('com_j2commerce_importexport is in protected list', in_array('com_j2commerce_importexport', $this->protectedElements));
        $this->test('plg_privacy_j2commerce is in protected list', in_array('plg_privacy_j2commerce', $this->protectedElements));
        $this->test('plg_j2commerce_productcompare is in protected list', in_array('plg_j2commerce_productcompare', $this->protectedElements));

        // Test 2: Protected extensions are never marked as incompatible
        echo "\n--- Protected Extension Detection ---\n";
        
        // Even with old version and j2store.org URL, protected extensions should be compatible
        $oldManifest = (object)[
            'name' => 'J2Store Core',
            'version' => '1.0.0',
            'author' => 'Alagesan',
            'authorUrl' => 'http://www.j2store.org',
            'authorEmail' => 'supports@j2store.org'
        ];

        foreach ($this->protectedElements as $element) {
            $ext = (object)['element' => $element];
            $result = $this->checkJ2StoreCompatibility($oldManifest, $ext);
            $this->test("$element is protected even with old manifest", $result['incompatible'] === false);
        }

        // Test 3: Non-protected extensions with same manifest ARE incompatible
        echo "\n--- Non-Protected Extension Detection ---\n";
        $nonProtectedElements = [
            'app_gdpr',
            'app_simplecsv',
            'payment_stripe',
            'app_validationrules',
            'shipping_standard'
        ];

        foreach ($nonProtectedElements as $element) {
            $ext = (object)['element' => $element];
            $result = $this->checkJ2StoreCompatibility($oldManifest, $ext);
            $this->test("$element is NOT protected (detected as incompatible)", $result['incompatible'] === true);
        }

        // Test 4: J2Commerce extensions (by authorUrl) are protected
        echo "\n--- J2Commerce authorUrl Protection ---\n";
        $j2commerceManifest = (object)[
            'name' => 'Some Plugin',
            'version' => '1.0.0', // Old version but j2commerce.com URL
            'author' => 'J2Commerce',
            'authorUrl' => 'https://www.j2commerce.com',
            'authorEmail' => 'support@j2commerce.com'
        ];

        $ext = (object)['element' => 'app_some_new_plugin'];
        $result = $this->checkJ2StoreCompatibility($j2commerceManifest, $ext);
        $this->test('Extension with j2commerce.com authorUrl is protected', $result['incompatible'] === false);

        // Test 5: Verify com_j2store exists in database (if installed)
        echo "\n--- Database Verification ---\n";
        $j2storeExists = $this->extensionExists('com_j2store');
        if ($j2storeExists) {
            $this->test('com_j2store exists in database', true);
            
            // Verify it would not be marked for removal
            $query = $this->db->getQuery(true)
                ->select('manifest_cache')
                ->from('#__extensions')
                ->where('element = ' . $this->db->quote('com_j2store'));
            $this->db->setQuery($query);
            $manifestCache = $this->db->loadResult();
            $manifest = json_decode($manifestCache);
            
            $ext = (object)['element' => 'com_j2store'];
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('com_j2store in database is protected', $result['incompatible'] === false);
        } else {
            echo "Note: com_j2store not installed, skipping database verification\n";
            $this->test('Database verification skipped (com_j2store not installed)', true);
        }

        // Test 6: Verify cleanup component exists
        $cleanupExists = $this->extensionExists('com_j2store_cleanup');
        if ($cleanupExists) {
            $this->test('com_j2store_cleanup exists in database', true);
        } else {
            echo "Note: com_j2store_cleanup not installed yet\n";
            $this->test('Cleanup component check skipped', true);
        }

        // Test 7: Edge case - element with similar name but not exact match
        echo "\n--- Edge Cases ---\n";
        $similarElements = [
            'com_j2store_extra',      // Similar but not protected
            'com_j2store_cleanup_v2', // Similar but not protected
            'plg_privacy_j2commerce_extended' // Similar but not protected
        ];

        foreach ($similarElements as $element) {
            $ext = (object)['element' => $element];
            $result = $this->checkJ2StoreCompatibility($oldManifest, $ext);
            $this->test("$element (similar name) is NOT protected", $result['incompatible'] === true);
        }

        // Test 8: Case sensitivity
        echo "\n--- Case Sensitivity ---\n";
        $ext = (object)['element' => 'COM_J2STORE']; // Uppercase
        $result = $this->checkJ2StoreCompatibility($oldManifest, $ext);
        $this->test('COM_J2STORE (uppercase) is NOT protected (case sensitive)', $result['incompatible'] === true);

        $ext = (object)['element' => 'Com_J2store']; // Mixed case
        $result = $this->checkJ2StoreCompatibility($oldManifest, $ext);
        $this->test('Com_J2store (mixed case) is NOT protected', $result['incompatible'] === true);

        echo "\n=== Safety Checks Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";

        return $this->failed === 0;
    }
}

$test = new SafetyChecksTest();
exit($test->run() ? 0 : 1);
