<?php
/**
 * Scanning Tests for J2Store Cleanup v1.1.0
 * Tests the incompatibility detection logic
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ScanningTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $createdExtensions = [];

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    /**
     * Replicate the detection function from j2store_cleanup.php
     */
    private function checkJ2StoreCompatibility($manifest, $ext): array
    {
        $protectedElements = [
            'com_j2store',
            'com_j2store_cleanup',
            'com_j2commerce_importexport',
            'plg_privacy_j2commerce',
            'plg_j2commerce_productcompare'
        ];

        if (in_array($ext->element, $protectedElements)) {
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
     * Create a mock extension in the database
     */
    private function createMockExtension(string $element, string $name, array $manifest): int
    {
        $query = $this->db->getQuery(true)
            ->insert('#__extensions')
            ->columns(['name', 'type', 'element', 'folder', 'enabled', 'manifest_cache'])
            ->values(
                $this->db->quote($name) . ',' .
                $this->db->quote('plugin') . ',' .
                $this->db->quote($element) . ',' .
                $this->db->quote('j2store') . ',' .
                '0,' .
                $this->db->quote(json_encode($manifest))
            );
        $this->db->setQuery($query);
        $this->db->execute();
        $id = $this->db->insertid();
        $this->createdExtensions[] = $id;
        return $id;
    }

    /**
     * Clean up created mock extensions
     */
    private function cleanup(): void
    {
        if (!empty($this->createdExtensions)) {
            $query = $this->db->getQuery(true)
                ->delete('#__extensions')
                ->where('extension_id IN (' . implode(',', $this->createdExtensions) . ')');
            $this->db->setQuery($query);
            $this->db->execute();
        }
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
        echo "=== Scanning Tests (v1.1.0 Detection Logic) ===\n\n";

        try {
            // Test 1: Version < 4.0.0 should be incompatible
            echo "--- Version Detection ---\n";
            $manifest = (object)[
                'name' => 'Test Plugin',
                'version' => '1.0.16',
                'author' => 'Test',
                'authorUrl' => 'https://example.com',
                'authorEmail' => 'test@example.com'
            ];
            $ext = (object)['element' => 'app_test_old'];
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('Version 1.0.16 detected as incompatible', $result['incompatible'] === true);
            $this->test('Reason contains version info', strpos($result['reason'], '1.0.16') !== false);

            // Test 2: Version >= 4.0.0 should be compatible
            $manifest->version = '4.0.4';
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('Version 4.0.4 detected as compatible', $result['incompatible'] === false);

            // Test 3: Version 3.9.9 should be incompatible
            $manifest->version = '3.9.9';
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('Version 3.9.9 detected as incompatible', $result['incompatible'] === true);

            // Test 4: authorUrl j2store.org should be incompatible
            echo "\n--- authorUrl Detection ---\n";
            $manifest = (object)[
                'name' => 'Test Plugin',
                'version' => '4.0.0',
                'author' => 'Alagesan',
                'authorUrl' => 'http://www.j2store.org',
                'authorEmail' => 'test@example.com'
            ];
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('authorUrl j2store.org detected as incompatible', $result['incompatible'] === true);
            $this->test('Reason mentions j2store.org', strpos($result['reason'], 'j2store.org') !== false);

            // Test 5: authorUrl j2commerce.com should be compatible
            $manifest->authorUrl = 'https://www.j2commerce.com';
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('authorUrl j2commerce.com detected as compatible', $result['incompatible'] === false);

            // Test 6: authorEmail @j2store.org should be incompatible
            echo "\n--- authorEmail Detection ---\n";
            $manifest = (object)[
                'name' => 'Test Plugin',
                'version' => '4.0.0',
                'author' => 'Test',
                'authorUrl' => 'https://example.com',
                'authorEmail' => 'supports@j2store.org'
            ];
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('authorEmail @j2store.org detected as incompatible', $result['incompatible'] === true);
            $this->test('Reason mentions @j2store.org', strpos($result['reason'], '@j2store.org') !== false);

            // Test 7: authorEmail @j2commerce.com should be compatible
            $manifest->authorEmail = 'support@j2commerce.com';
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('authorEmail @j2commerce.com detected as compatible', $result['incompatible'] === false);

            // Test 8: Null manifest should be incompatible
            echo "\n--- Null/Invalid Manifest Handling ---\n";
            $result = $this->checkJ2StoreCompatibility(null, $ext);
            $this->test('Null manifest detected as incompatible', $result['incompatible'] === true);
            $this->test('Reason mentions invalid manifest', strpos($result['reason'], 'Invalid') !== false);

            // Test 9: Empty object manifest
            $result = $this->checkJ2StoreCompatibility((object)[], $ext);
            $this->test('Empty manifest with unknown version is compatible', $result['incompatible'] === false);

            // Test 10: Protected extensions
            echo "\n--- Protected Extensions ---\n";
            $manifest = (object)[
                'name' => 'J2Store',
                'version' => '1.0.0',
                'authorUrl' => 'http://www.j2store.org'
            ];
            $ext = (object)['element' => 'com_j2store'];
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('com_j2store is protected (not incompatible)', $result['incompatible'] === false);

            $ext = (object)['element' => 'com_j2store_cleanup'];
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('com_j2store_cleanup is protected', $result['incompatible'] === false);

            $ext = (object)['element' => 'plg_privacy_j2commerce'];
            $result = $this->checkJ2StoreCompatibility($manifest, $ext);
            $this->test('plg_privacy_j2commerce is protected', $result['incompatible'] === false);

            // Test 11: Database integration - create mock and verify detection
            echo "\n--- Database Integration ---\n";
            $mockId = $this->createMockExtension('app_test_legacy', 'Legacy Test Plugin', [
                'name' => 'Legacy Test Plugin',
                'version' => '1.5.0',
                'author' => 'Alagesan',
                'authorUrl' => 'http://www.j2store.org',
                'authorEmail' => 'supports@j2store.org'
            ]);
            $this->test('Mock extension created in database', $mockId > 0);

            // Query and verify
            $query = $this->db->getQuery(true)
                ->select('*')
                ->from('#__extensions')
                ->where('extension_id = ' . (int)$mockId);
            $this->db->setQuery($query);
            $dbExt = $this->db->loadObject();
            $this->test('Mock extension retrieved from database', $dbExt !== null);

            if ($dbExt) {
                $manifest = json_decode($dbExt->manifest_cache);
                $result = $this->checkJ2StoreCompatibility($manifest, $dbExt);
                $this->test('Mock extension detected as incompatible', $result['incompatible'] === true);
            }

            // Test 12: Real-world example - old GDPR plugin
            echo "\n--- Real-World Examples ---\n";
            $gdprOld = (object)[
                'name' => 'GDPR',
                'version' => '1.0.16',
                'creationDate' => 'APR 2021',
                'author' => 'Alagesan',
                'authorUrl' => 'http://www.j2store.org',
                'authorEmail' => 'supports@j2store.org'
            ];
            $ext = (object)['element' => 'app_gdpr'];
            $result = $this->checkJ2StoreCompatibility($gdprOld, $ext);
            $this->test('Old GDPR v1.0.16 detected as incompatible', $result['incompatible'] === true);

            // New GDPR plugin
            $gdprNew = (object)[
                'name' => 'GDPR',
                'version' => '4.0.4',
                'creationDate' => 'NOV 2024',
                'author' => 'J2Commerce',
                'authorUrl' => 'https://www.j2commerce.com',
                'authorEmail' => 'support@j2commerce.com'
            ];
            $result = $this->checkJ2StoreCompatibility($gdprNew, $ext);
            $this->test('New GDPR v4.0.4 detected as compatible', $result['incompatible'] === false);

        } finally {
            $this->cleanup();
        }

        echo "\n=== Scanning Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";

        return $this->failed === 0;
    }
}

$test = new ScanningTest();
exit($test->run() ? 0 : 1);
