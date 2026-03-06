<?php
/**
 * Component Functions Tests for J2Store Cleanup
 * Validates the main component file has all required functions.
 */

class ComponentFunctionsTest
{
    private $passed = 0;
    private $failed = 0;
    private $mainFile = '/var/www/html/administrator/components/com_j2store_cleanup/j2store_cleanup.php';

    public function run(): bool
    {
        echo "=== Component Functions Tests ===\n\n";

        $content = file_get_contents($this->mainFile);

        $this->test('Main file is readable', function () use ($content) {
            return !empty($content) && strlen($content) > 100;
        });

        $this->test('Has getExtensionPath function', function () use ($content) {
            return strpos($content, 'function getExtensionPath') !== false;
        });

        $this->test('Has getIssuePatterns function', function () use ($content) {
            return strpos($content, 'function getIssuePatterns') !== false;
        });

        $this->test('Has scanForIssues function', function () use ($content) {
            return strpos($content, 'function scanForIssues') !== false;
        });

        $this->test('Has classifyExtension function', function () use ($content) {
            return strpos($content, 'function classifyExtension') !== false;
        });

        $this->test('Scans plugins directory', function () use ($content) {
            return strpos($content, 'plugins') !== false;
        });

        $this->test('Scans components directory', function () use ($content) {
            return strpos($content, 'components') !== false;
        });

        $this->test('Handles manifest XML parsing', function () use ($content) {
            return strpos($content, 'simplexml') !== false
                || strpos($content, 'SimpleXML') !== false
                || strpos($content, 'manifest') !== false;
        });

        echo "\n=== Component Functions Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function test(string $name, callable $fn): void
    {
        try {
            if ($fn()) { echo "✓ {$name}\n"; $this->passed++; }
            else { echo "✗ {$name}\n"; $this->failed++; }
        } catch (\Exception $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new ComponentFunctionsTest();
exit($test->run() ? 0 : 1);
