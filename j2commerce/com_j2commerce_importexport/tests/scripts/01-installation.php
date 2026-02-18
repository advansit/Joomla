<?php
/**
 * Installation Tests for J2Commerce Import/Export
 * Uses direct MySQL connection instead of Joomla framework.
 */

class InstallationTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $dbPrefix;

    public function __construct()
    {
        // Read DB config from Joomla configuration.php
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
        echo "=== Installation Tests ===\n\n";

        $this->test('Component registered in extensions table', function() {
            $result = $this->db->query("SELECT extension_id FROM {$this->dbPrefix}extensions WHERE element = 'com_j2commerce_importexport' AND type = 'component'");
            return $result && $result->num_rows > 0;
        });

        $this->test('Component is enabled', function() {
            $result = $this->db->query("SELECT enabled FROM {$this->dbPrefix}extensions WHERE element = 'com_j2commerce_importexport'");
            $row = $result ? $result->fetch_assoc() : null;
            return $row && $row['enabled'] == 1;
        });

        $this->test('Admin menu entry exists', function() {
            $result = $this->db->query("SELECT id FROM {$this->dbPrefix}menu WHERE link LIKE '%com_j2commerce_importexport%' AND client_id = 1");
            return $result && $result->num_rows > 0;
        });

        $this->test('ExportModel file exists', function() {
            return file_exists('/var/www/html/administrator/components/com_j2commerce_importexport/src/Model/ExportModel.php');
        });

        $this->test('ImportModel file exists', function() {
            return file_exists('/var/www/html/administrator/components/com_j2commerce_importexport/src/Model/ImportModel.php');
        });

        $this->test('Language files installed (en-GB)', function() {
            return file_exists('/var/www/html/administrator/language/en-GB/com_j2commerce_importexport.ini');
        });

        echo "\n=== Installation Test Summary ===\n";
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

$test = new InstallationTest();
exit($test->run() ? 0 : 1);
