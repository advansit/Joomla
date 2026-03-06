<?php
/**
 * Uninstall Tests for J2Commerce Product Compare Plugin
 * Verifies clean removal of the plugin.
 */

class UninstallTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $dbPrefix;

    public function __construct()
    {
        require '/var/www/html/configuration.php';
        $config = new JConfig();
        $this->dbPrefix = $config->dbprefix;
        $this->db = new mysqli($config->host, $config->user, $config->password, $config->db);
        if ($this->db->connect_error) {
            die("DB connection failed: " . $this->db->connect_error . "\n");
        }
    }

    public function run(): bool
    {
        echo "=== Uninstall Tests ===\n\n";

        // Get extension ID before uninstall
        $result = $this->db->query("SELECT extension_id FROM {$this->dbPrefix}extensions WHERE element = 'productcompare' AND type = 'plugin' AND folder = 'j2store'");
        $row = $result ? $result->fetch_assoc() : null;
        $extensionId = $row ? (int) $row['extension_id'] : 0;

        $this->test('Extension ID found before uninstall', function () use ($extensionId) {
            return $extensionId > 0;
        });

        // Uninstall via Joomla CLI
        $output = [];
        $exitCode = 0;
        exec("php /var/www/html/cli/joomla.php extension:remove $extensionId --no-interaction 2>&1", $output, $exitCode);
        $outputStr = implode("\n", $output);

        $this->test('Uninstall command executed', function () use ($exitCode, $outputStr) {
            echo "  Output: $outputStr\n";
            return $exitCode === 0;
        });

        $this->test('Plugin removed from #__extensions', function () {
            $result = $this->db->query("SELECT COUNT(*) as cnt FROM {$this->dbPrefix}extensions WHERE element = 'productcompare' AND folder = 'j2store'");
            $row = $result ? $result->fetch_assoc() : null;
            return $row && (int) $row['cnt'] === 0;
        });

        $this->test('Plugin files removed', function () {
            return !file_exists('/var/www/html/plugins/j2store/productcompare/src/Extension/ProductCompare.php');
        });

        echo "\n=== Uninstall Test Summary ===\n";
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

$test = new UninstallTest();
exit($test->run() ? 0 : 1);
