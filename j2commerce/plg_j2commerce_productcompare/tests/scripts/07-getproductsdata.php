<?php
/**
 * getProductsData() Round-Trip Tests
 *
 * Seeds product fixtures → calls getProductsData() via reflection →
 * verifies returned data contains title, SKU, price, options.
 * Also tests: disabled product excluded, getProductOptions() with/without options.
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\Event\Dispatcher;
use Joomla\Registry\Registry;

class GetProductsDataTest
{
    private $db;
    private $passed = 0;
    private $failed = 0;
    private $seededProductIds  = [];
    private $seededVariantIds  = [];
    private $seededContentIds  = [];
    private $seededOptionIds   = [];

    public function __construct()
    {
        $this->db = Factory::getContainer()->get('DatabaseDriver');
    }

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

    public function run(): bool
    {
        echo "=== getProductsData() Round-Trip Tests ===\n\n";

        try {
            $this->seedFixtures();
            $this->testGetProductsData();
            $this->testDisabledProductExcluded();
            $this->testGetProductOptions();
        } finally {
            $this->cleanupFixtures();
        }

        echo "\n=== getProductsData() Test Summary ===\n";
        echo "Passed: {$this->passed}\n";
        echo "Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    // -------------------------------------------------------------------------

    private function testGetProductsData(): void
    {
        echo "--- getProductsData() round-trip ---\n";

        $plugin = $this->makePlugin();
        $rc     = new ReflectionClass($plugin);
        $method = $rc->getMethod('getProductsData');
        $method->setAccessible(true);

        $products = $method->invoke($plugin, $this->seededProductIds);

        $this->test('Returns array', is_array($products));
        $this->test('Returns 2 products (enabled only)', count($products) === 2,
            'Got ' . count($products));

        if (!empty($products)) {
            $p = $products[0];
            $this->test('Product has j2store_product_id', isset($p->j2store_product_id));
            $this->test('Product has title',              isset($p->title) && !empty($p->title));
            $this->test('Product has sku',                isset($p->sku));
            $this->test('Product has price',              isset($p->price));
            $this->test('Product has options key',        isset($p->options) && is_array($p->options));

            // Product 1 has options — verify they're loaded
            $p1 = array_values(array_filter($products, fn($x) => (int)$x->j2store_product_id === $this->seededProductIds[0]))[0] ?? null;
            if ($p1) {
                $this->test('Product with options: options not empty', !empty($p1->options),
                    'Expected options for product ' . $this->seededProductIds[0]);
                $optionNames = array_column($p1->options, 'option_name');
                $this->test('Option name "Colour" present', in_array('Colour', $optionNames));
            }

            // Product 2 has no options — verify empty array, no crash
            $p2 = array_values(array_filter($products, fn($x) => (int)$x->j2store_product_id === $this->seededProductIds[1]))[0] ?? null;
            if ($p2) {
                $this->test('Product without options: options is empty array',
                    isset($p2->options) && $p2->options === []);
            }
        }
    }

    private function testDisabledProductExcluded(): void
    {
        echo "\n--- Disabled product excluded ---\n";

        $plugin = $this->makePlugin();
        $rc     = new ReflectionClass($plugin);
        $method = $rc->getMethod('getProductsData');
        $method->setAccessible(true);

        // seededProductIds[2] is the disabled product
        $allThree = $method->invoke($plugin, $this->seededProductIds);
        $returnedIds = array_map(fn($p) => (int)$p->j2store_product_id, $allThree);

        $this->test('Disabled product not in results',
            !in_array($this->seededProductIds[2], $returnedIds),
            'Disabled product ID ' . $this->seededProductIds[2] . ' should be excluded');
    }

    private function testGetProductOptions(): void
    {
        echo "\n--- getProductOptions() ---\n";

        $plugin = $this->makePlugin();
        $rc     = new ReflectionClass($plugin);
        $method = $rc->getMethod('getProductOptions');
        $method->setAccessible(true);

        // Product with options
        $opts = $method->invoke($plugin, $this->seededProductIds[0]);
        $this->test('Product with options: returns array',    is_array($opts));
        $this->test('Product with options: not empty',        !empty($opts));
        $this->test('Option has option_name key',             isset($opts[0]['option_name']));
        $this->test('Option has option_value key',            isset($opts[0]['option_value']));

        // Product without options
        $opts2 = $method->invoke($plugin, $this->seededProductIds[1]);
        $this->test('Product without options: returns empty array', $opts2 === []);

        // Non-existent product ID — must not crash
        try {
            $opts3 = $method->invoke($plugin, 999999999);
            $this->test('Non-existent product ID: no crash', true);
            $this->test('Non-existent product ID: returns empty array', $opts3 === []);
        } catch (\Throwable $e) {
            $this->test('Non-existent product ID: no crash', false, $e->getMessage());
        }
    }

    // -------------------------------------------------------------------------
    // Fixtures
    // -------------------------------------------------------------------------

    private function seedFixtures(): void
    {
        $ts = time();

        // Content articles (product source)
        foreach (['Test Product Alpha', 'Test Product Beta', 'Test Product Disabled'] as $i => $title) {
            $article = (object)[
                'title'     => $title,
                'alias'     => 'test-product-' . $i . '-' . $ts,
                'introtext' => 'Description for ' . $title,
                'fulltext'  => '',
                'state'     => 1,
                'catid'     => 2,
                'created'   => date('Y-m-d H:i:s'),
                'created_by' => 42,
                'access'    => 1,
                'language'  => '*',
                'params'    => '{}',
                'metadata'  => '{}',
                'attribs'   => '{}',
            ];
            $this->db->insertObject('#__content', $article, 'id');
            $this->seededContentIds[] = (int)$this->db->insertid();
        }

        // J2Store products
        foreach ([
            ['enabled' => 1, 'idx' => 0],
            ['enabled' => 1, 'idx' => 1],
            ['enabled' => 0, 'idx' => 2],  // disabled
        ] as $p) {
            $product = (object)[
                'product_source_id'  => $this->seededContentIds[$p['idx']],
                'product_source'     => 'com_content',
                'product_type'       => 'simple',
                'enabled'            => $p['enabled'],
                'taxprofile_id'      => 0,
                'params'             => '{}',
            ];
            $this->db->insertObject('#__j2store_products', $product, 'j2store_product_id');
            $this->seededProductIds[] = (int)$this->db->insertid();
        }

        // Variants
        foreach ($this->seededProductIds as $i => $productId) {
            $variant = (object)[
                'product_id'   => $productId,
                'sku'          => 'TEST-SKU-' . $i . '-' . $ts,
                'price'        => 10.00 + ($i * 5),
                'stock'        => 100,
                'availability' => '',
                'params'       => '{}',
                'isdefault'    => 1,
            ];
            $this->db->insertObject('#__j2store_variants', $variant, 'j2store_variant_id');
            $this->seededVariantIds[] = (int)$this->db->insertid();
        }

        // Options for product 0 only
        foreach ([
            ['option_name' => 'Colour', 'option_value' => 'Red'],
            ['option_name' => 'Size',   'option_value' => 'M'],
        ] as $opt) {
            $option = (object)[
                'product_id'   => $this->seededProductIds[0],
                'option_name'  => $opt['option_name'],
                'option_value' => $opt['option_value'],
            ];
            $this->db->insertObject('#__j2store_product_options', $option, 'j2store_product_option_id');
            $this->seededOptionIds[] = (int)$this->db->insertid();
        }
    }

    private function cleanupFixtures(): void
    {
        foreach ([
            ['#__j2store_product_options', 'j2store_product_option_id', $this->seededOptionIds],
            ['#__j2store_variants',        'j2store_variant_id',        $this->seededVariantIds],
            ['#__j2store_products',        'j2store_product_id',        $this->seededProductIds],
            ['#__content',                 'id',                        $this->seededContentIds],
        ] as [$table, $pk, $ids]) {
            if (empty($ids)) continue;
            try {
                $this->db->setQuery(
                    $this->db->getQuery(true)->delete($table)->whereIn($pk, $ids)
                )->execute();
            } catch (\Exception $e) {
                // non-fatal
            }
        }
    }

    private function makePlugin(): \Advans\Plugin\J2Commerce\ProductCompare\Extension\ProductCompare
    {
        $dispatcher = new Dispatcher();
        $params     = new Registry(['max_products' => 4, 'show_in_list' => 1, 'show_in_detail' => 1]);
        return new \Advans\Plugin\J2Commerce\ProductCompare\Extension\ProductCompare(
            $dispatcher,
            ['params' => $params]
        );
    }
}

$test = new GetProductsDataTest();
exit($test->run() ? 0 : 1);
