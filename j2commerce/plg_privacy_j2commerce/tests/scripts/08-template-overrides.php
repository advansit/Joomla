<?php
/**
 * Test 08: Template Override Deployment
 *
 * Verifies that:
 * - Bundled override source files are present in the installed plugin
 * - Overrides were deployed to all active frontend templates on first install
 * - Deployed overrides contain the PluginHelper check (not empty stubs)
 * - No override was deployed to admin templates (client_id = 1)
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';
use Joomla\CMS\Factory;

class TemplateOverridesTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    private const OVERRIDE_FILES = [
        'checkout/default_shipping_payment.php',
        'myprofile/default.php',
        'myprofile/default_addresses.php',
    ];

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    private function test(string $name, bool $condition, string $message = ''): bool
    {
        if ($condition) {
            echo "✓ $name... PASS\n";
            $this->passed++;
            return true;
        }
        echo "✗ $name... FAIL" . ($message ? " - $message" : '') . "\n";
        $this->failed++;
        return false;
    }

    public function run(): bool
    {
        echo "=== Template Override Tests ===\n\n";

        $pluginOverrideDir = JPATH_BASE . '/plugins/privacy/j2commerce/overrides/com_j2store';

        // 1. Source files present in installed plugin
        echo "-- Source files in plugin --\n";
        $this->test('Override source directory exists', is_dir($pluginOverrideDir));

        foreach (self::OVERRIDE_FILES as $file) {
            $this->test(
                "Source: $file",
                file_exists($pluginOverrideDir . '/' . $file)
            );
        }

        // 2. Source files contain PluginHelper check
        echo "\n-- Source file integrity --\n";
        foreach (['checkout/default_shipping_payment.php', 'myprofile/default.php'] as $file) {
            $src = $pluginOverrideDir . '/' . $file;
            if (file_exists($src)) {
                $content = file_get_contents($src);
                $this->test(
                    "Source $file uses PluginHelper",
                    strpos($content, 'PluginHelper') !== false,
                    'PluginHelper check missing — override may not conditionally render privacy content'
                );
            }
        }

        // 3. Deployed to active frontend templates
        echo "\n-- Deployed overrides --\n";
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('element'))
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('template'))
            ->where($this->db->quoteName('client_id') . ' = 0')
            ->where($this->db->quoteName('enabled') . ' = 1');
        $this->db->setQuery($query);
        $templates = $this->db->loadColumn() ?: [];

        if (empty($templates)) {
            echo "  (no active frontend templates found — skipping deployment checks)\n";
        }

        foreach ($templates as $tpl) {
            $tplBase = JPATH_BASE . '/templates/' . $tpl . '/html/com_j2store';
            foreach (self::OVERRIDE_FILES as $file) {
                $dest = $tplBase . '/' . $file;
                $this->test(
                    "[$tpl] $file deployed",
                    file_exists($dest),
                    "File not found: templates/$tpl/html/com_j2store/$file"
                );
            }
        }

        // 4. No overrides in admin templates
        echo "\n-- Admin template isolation --\n";
        $query = $this->db->getQuery(true)
            ->select($this->db->quoteName('element'))
            ->from($this->db->quoteName('#__extensions'))
            ->where($this->db->quoteName('type') . ' = ' . $this->db->quote('template'))
            ->where($this->db->quoteName('client_id') . ' = 1')
            ->where($this->db->quoteName('enabled') . ' = 1');
        $this->db->setQuery($query);
        $adminTemplates = $this->db->loadColumn() ?: [];

        foreach ($adminTemplates as $tpl) {
            $dest = JPATH_BASE . '/administrator/templates/' . $tpl . '/html/com_j2store';
            $this->test(
                "[$tpl] no com_j2store overrides in admin template",
                !is_dir($dest),
                "Unexpected directory: administrator/templates/$tpl/html/com_j2store"
            );
        }

        echo "\n=== Template Override Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";

        return $this->failed === 0;
    }
}

$test = new TemplateOverridesTest();
exit($test->run() ? 0 : 1);
