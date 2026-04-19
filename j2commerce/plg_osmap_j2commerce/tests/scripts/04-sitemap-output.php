<?php
/**
 * Sitemap Output Tests for OSMap J2Commerce Plugin
 *
 * Verifies the plugin produces correct sitemap nodes for J2Commerce products.
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class SitemapOutputTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Sitemap Output Tests ===\n\n";

        $this->test('com_j2store shop menu item exists', function () {
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__menu')
                ->where('link LIKE ' . $this->db->quote('%com_j2store%'))
                ->where('published = 1')
                ->where('client_id = 0');
            return (int) $this->db->setQuery($query)->loadResult() > 0;
        });

        $this->test('J2Commerce products with published=-2 menu items exist', function () {
            $query = $this->db->getQuery(true)
                ->select('COUNT(*)')
                ->from('#__menu AS m')
                ->join('INNER', '#__j2store_products AS p ON m.link LIKE CONCAT(\'%id=\', p.product_source_id)')
                ->where('m.published = -2')
                ->where('p.enabled = 1')
                ->where('m.client_id = 0');
            return (int) $this->db->setQuery($query)->loadResult() > 0;
        });

        $this->test('Sitemap XML is accessible', function () {
            $url = 'http://localhost/de/sitemap';
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $content = @file_get_contents($url, false, $ctx);
            return $content !== false && strpos($content, '<urlset') !== false;
        });

        $this->test('Sitemap contains product URLs', function () {
            $url = 'http://localhost/de/sitemap';
            $ctx = stream_context_create(['http' => ['timeout' => 10]]);
            $content = @file_get_contents($url, false, $ctx);
            if ($content === false) return false;
            preg_match_all('|localhost/de/[^]]+|', $content, $matches);
            $shopUrls = array_filter($matches[0], fn($u) => strpos($u, '/shop') !== false || strpos($u, '/component/content') !== false);
            return count($shopUrls) > 0;
        });

        echo "\n=== Sitemap Output Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function test(string $name, callable $fn): void
    {
        try {
            if ($fn()) { echo "✓ {$name}\n"; $this->passed++; }
            else       { echo "✗ {$name}\n"; $this->failed++; }
        } catch (\Exception $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new SitemapOutputTest();
exit($test->run() ? 0 : 1);
