<?php
/**
 * Cleanup Tests for J2Store Cleanup v1.0.0
 * Tests the extension removal logic (unit tests only, no DB operations)
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

class CleanupTest
{
    private $passed = 0;
    private $failed = 0;

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
        echo "=== Cleanup Tests (v1.0.0 Removal Logic) ===\n\n";

        // Test 1: Empty selection handling
        echo "--- Input Validation ---\n";
        $emptyIds = [];
        $emptyIds = array_map('intval', $emptyIds);
        $emptyIds = array_filter($emptyIds);
        $this->test('Empty selection results in empty array', empty($emptyIds));

        // Test 2: Zero ID filtering
        $mixedIds = [0, 1, 0, 2, 0];
        $filteredIds = array_filter(array_map('intval', $mixedIds));
        $this->test('Zero IDs filtered out', !in_array(0, $filteredIds));
        $this->test('Valid IDs preserved', in_array(1, $filteredIds) && in_array(2, $filteredIds));

        // Test 3: SQL injection prevention
        echo "\n--- Security ---\n";
        $maliciousInput = ["1; DROP TABLE #__extensions; --", "1 OR 1=1", "abc", ""];
        $sanitized = array_map('intval', $maliciousInput);
        $this->test('SQL injection string sanitized to integer', $sanitized[0] === 1);
        $this->test('OR injection sanitized to integer', $sanitized[1] === 1);
        $this->test('Non-numeric string sanitized to 0', $sanitized[2] === 0);
        $this->test('Empty string sanitized to 0', $sanitized[3] === 0);

        // Test 4: Array filtering removes zeros
        $sanitizedFiltered = array_filter($sanitized);
        $this->test('Zeros removed after filtering', count($sanitizedFiltered) === 2);

        // Test 5: ID validation
        echo "\n--- ID Validation ---\n";
        $validIds = [100, 200, 300];
        $this->test('Valid IDs pass through', count(array_filter(array_map('intval', $validIds))) === 3);

        $negativeIds = [-1, -100, 0];
        $filteredNegative = array_filter(array_map('intval', $negativeIds), function($id) { return $id > 0; });
        $this->test('Negative IDs filtered out', empty($filteredNegative));

        // Test 6: Large numbers
        $largeIds = [999999999, 2147483647];
        $sanitizedLarge = array_map('intval', $largeIds);
        $this->test('Large IDs preserved', $sanitizedLarge[0] === 999999999);

        // Test 7: Implode for SQL
        echo "\n--- SQL Generation ---\n";
        $ids = [1, 2, 3];
        $sql = 'extension_id IN (' . implode(',', $ids) . ')';
        $this->test('SQL IN clause generated correctly', $sql === 'extension_id IN (1,2,3)');

        $singleId = [42];
        $sqlSingle = 'extension_id IN (' . implode(',', $singleId) . ')';
        $this->test('Single ID SQL generated correctly', $sqlSingle === 'extension_id IN (42)');

        echo "\n=== Cleanup Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        echo "Total:  " . ($this->passed + $this->failed) . "\n";

        return $this->failed === 0;
    }
}

$test = new CleanupTest();
exit($test->run() ? 0 : 1);
