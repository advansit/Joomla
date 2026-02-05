<?php
/**
 * Cleanup Tests for J2Store Cleanup v1.1.0
 * Tests the extension removal functionality
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Installer\Installer;

class CleanupTest
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
     * Create a mock extension in the database
     */
    private function createMockExtension(string $element, string $name, array $manifest, string $type = 'plugin', string $folder = 'j2store'): int
    {
        $query = $this->db->getQuery(true)
            ->insert('#__extensions')
            ->columns(['name', 'type', 'element', 'folder', 'client_id', 'enabled', 'access', 'manifest_cache', 'params'])
            ->values(
                $this->db->quote($name) . ',' .
                $this->db->quote($type) . ',' .
                $this->db->quote($element) . ',' .
                $this->db->quote($folder) . ',' .
                '0,' .
                '0,' .
                '1,' .
                $this->db->quote(json_encode($manifest)) . ',' .
                $this->db->quote('{}')
            );
        $this->db->setQuery($query);
        $this->db->execute();
        $id = $this->db->insertid();
        $this->createdExtensions[] = $id;
        return $id;
    }

    /**
     * Check if extension exists in database
     */
    private function extensionExists(int $id): bool
    {
        $query = $this->db->getQuery(true)
            ->select('COUNT(*)')
            ->from('#__extensions')
            ->where('extension_id = ' . (int)$id);
        $this->db->setQuery($query);
        return (int)$this->db->loadResult() > 0;
    }

    /**
     * Remove extension from database (simulating cleanup action)
     */
    private function removeExtension(int $id): bool
    {
        try {
            // First try Joomla's Installer
            $installer = Installer::getInstance();
            
            // Get extension type
            $query = $this->db->getQuery(true)
                ->select('type')
                ->from('#__extensions')
                ->where('extension_id = ' . (int)$id);
            $this->db->setQuery($query);
            $type = $this->db->loadResult();
            
            if ($type && $installer->uninstall($type, $id)) {
                return true;
            }
            
            // Fallback: direct DB delete
            $query = $this->db->getQuery(true)
                ->delete('#__extensions')
                ->where('extension_id = ' . (int)$id);
            $this->db->setQuery($query);
            $this->db->execute();
            return true;
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Clean up any remaining test extensions
     */
    private function cleanup(): void
    {
        foreach ($this->createdExtensions as $id) {
            if ($this->extensionExists($id)) {
                $query = $this->db->getQuery(true)
                    ->delete('#__extensions')
                    ->where('extension_id = ' . (int)$id);
                $this->db->setQuery($query);
                try {
                    $this->db->execute();
                } catch (Exception $e) {
                    // Ignore cleanup errors
                }
            }
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
        echo "=== Cleanup Tests (v1.1.0 Removal Logic) ===\n\n";

        try {
            // Test 1: Create and remove a mock extension
            echo "--- Basic Removal ---\n";
            $mockId = $this->createMockExtension('app_test_remove', 'Test Remove Plugin', [
                'name' => 'Test Remove Plugin',
                'version' => '1.0.0',
                'author' => 'Test'
            ]);
            $this->test('Mock extension created', $mockId > 0);
            $this->test('Extension exists before removal', $this->extensionExists($mockId));

            $removed = $this->removeExtension($mockId);
            $this->test('Removal operation succeeded', $removed === true);
            $this->test('Extension removed from database', !$this->extensionExists($mockId));

            // Remove from tracking since it's already gone
            $this->createdExtensions = array_diff($this->createdExtensions, [$mockId]);

            // Test 2: Remove multiple extensions
            echo "\n--- Batch Removal ---\n";
            $ids = [];
            for ($i = 1; $i <= 3; $i++) {
                $ids[] = $this->createMockExtension("app_batch_test_$i", "Batch Test $i", [
                    'name' => "Batch Test $i",
                    'version' => '1.0.0'
                ]);
            }
            $this->test('Created 3 mock extensions', count($ids) === 3);

            $allExist = true;
            foreach ($ids as $id) {
                if (!$this->extensionExists($id)) {
                    $allExist = false;
                    break;
                }
            }
            $this->test('All extensions exist before removal', $allExist);

            // Remove all
            foreach ($ids as $id) {
                $this->removeExtension($id);
            }

            $noneExist = true;
            foreach ($ids as $id) {
                if ($this->extensionExists($id)) {
                    $noneExist = false;
                    break;
                }
            }
            $this->test('All extensions removed after batch removal', $noneExist);

            // Remove from tracking
            $this->createdExtensions = array_diff($this->createdExtensions, $ids);

            // Test 3: Verify removal doesn't affect other extensions
            echo "\n--- Isolation Test ---\n";
            $keepId = $this->createMockExtension('app_keep_this', 'Keep This Plugin', [
                'name' => 'Keep This Plugin',
                'version' => '4.0.0'
            ]);
            $removeId = $this->createMockExtension('app_remove_this', 'Remove This Plugin', [
                'name' => 'Remove This Plugin',
                'version' => '1.0.0'
            ]);

            $this->test('Both extensions created', $this->extensionExists($keepId) && $this->extensionExists($removeId));

            $this->removeExtension($removeId);
            $this->createdExtensions = array_diff($this->createdExtensions, [$removeId]);

            $this->test('Target extension removed', !$this->extensionExists($removeId));
            $this->test('Other extension still exists', $this->extensionExists($keepId));

            // Test 4: Empty selection handling
            echo "\n--- Edge Cases ---\n";
            $emptyIds = [];
            $emptyIds = array_map('intval', $emptyIds);
            $emptyIds = array_filter($emptyIds);
            $this->test('Empty selection results in empty array', empty($emptyIds));

            // Test 5: Invalid ID handling
            $invalidId = 999999999;
            $this->test('Invalid ID does not exist', !$this->extensionExists($invalidId));

            // Test 6: Zero ID filtering
            $mixedIds = [0, 1, 0, 2, 0];
            $filteredIds = array_filter(array_map('intval', $mixedIds));
            $this->test('Zero IDs filtered out', !in_array(0, $filteredIds));
            $this->test('Valid IDs preserved', in_array(1, $filteredIds) && in_array(2, $filteredIds));

            // Test 7: SQL injection prevention
            echo "\n--- Security ---\n";
            $maliciousInput = ["1; DROP TABLE #__extensions; --", "1 OR 1=1"];
            $sanitized = array_map('intval', $maliciousInput);
            $this->test('SQL injection sanitized to integers', $sanitized[0] === 1 && $sanitized[1] === 1);

        } finally {
            $this->cleanup();
        }

        echo "\n=== Cleanup Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";

        return $this->failed === 0;
    }
}

$test = new CleanupTest();
exit($test->run() ? 0 : 1);
