<?php
/**
 * Plugin Class Tests for OSMap J2Commerce Plugin
 */
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');
require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST']   = $_SERVER['HTTP_HOST']   ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
Factory::getDbo();

// Register plugin's PSR-4 namespace (composer install not run after JOOMLA_EXTENSIONS_PATHS)
spl_autoload_register(function (string $class): void {
    $prefix = 'Advans\\Plugin\\Osmap\\J2Commerce\\';
    $base   = JPATH_PLUGINS . '/osmap/j2commerce/src/';
    if (str_starts_with($class, $prefix)) {
        $file = $base . str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        if (file_exists($file)) {
            require_once $file;
        }
    }
});

// Register OSMap's namespace (installed via JOOMLA_EXTENSIONS_PATHS, no composer)
spl_autoload_register(function (string $class): void {
    $prefix = 'Alledia\\OSMap\\';
    // OSMap installs its libraries under administrator/components/com_osmap/libraries/
    $bases = [
        JPATH_ADMINISTRATOR . '/components/com_osmap/libraries/',
        JPATH_LIBRARIES . '/alledia/osmap/',
        JPATH_ROOT . '/libraries/alledia/osmap/',
    ];
    if (str_starts_with($class, $prefix)) {
        $rel = str_replace('\\', '/', substr($class, strlen($prefix))) . '.php';
        foreach ($bases as $base) {
            if (file_exists($base . $rel)) {
                require_once $base . $rel;
                return;
            }
        }
    }
});

class PluginClassTest
{
    private int $passed = 0;
    private int $failed = 0;

    public function run(): bool
    {
        echo "=== Plugin Class Tests ===\n\n";

        $this->test('Plugin file is loadable', function () {
            require_once JPATH_PLUGINS . '/osmap/j2commerce/j2commerce.php';
            return class_exists('PlgOsmapJ2commerce');
        });

        $this->test('Plugin extends Alledia\\OSMap\\Plugin\\Base', function () {
            return is_subclass_of('PlgOsmapJ2commerce', 'Alledia\\OSMap\\Plugin\\Base');
        });

        $this->test('getComponentElement() returns com_j2store', function () {
            $dispatcher = new \Joomla\Event\Dispatcher();
            $plugin = new PlgOsmapJ2commerce($dispatcher, [
                'name'   => 'j2commerce',
                'type'   => 'osmap',
                'params' => '{}',
            ]);
            return $plugin->getComponentElement() === 'com_j2store';
        });

        $this->test('getTree() method exists', function () {
            return method_exists('PlgOsmapJ2commerce', 'getTree');
        });

        echo "\n=== Plugin Class Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function test(string $name, callable $fn): void
    {
        try {
            if ($fn()) {
                echo "✓ {$name}\n";
                $this->passed++;
            } else {
                echo "✗ {$name}\n";
                $this->failed++;
            }
        } catch (\Throwable $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new PluginClassTest();
exit($test->run() ? 0 : 1);
