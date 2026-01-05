<?php
/**
 * Test 01: Installation
 * Tests extension package and basic setup
 */

class InstallationTest
{
    private $results = [];
    private $joomlaInstalled = false;
    
    public function run()
    {
        echo "=== Installation Test ===\n\n";
        
        $this->testPackageExists();
        $this->testPackageStructure();
        $this->testJoomlaEnvironment();
        
        // Only run Joomla-specific tests if Joomla is installed
        if ($this->joomlaInstalled) {
            $this->testInstallExtension();
            $this->testComponentRegistered();
        } else {
            echo "\n⚠️  Joomla not fully installed - skipping installation tests\n";
            echo "Package validation completed successfully.\n";
        }
        
        $this->printResults();
        
        return $this->allTestsPassed();
    }
    
    private function testPackageExists()
    {
        $packagePath = '/tmp/extension.zip';
        
        if (file_exists($packagePath)) {
            $this->pass("Package exists: $packagePath");
            $size = filesize($packagePath);
            echo "  Package size: " . round($size / 1024, 2) . " KB\n";
        } else {
            $this->fail("Package not found: $packagePath");
        }
    }
    
    private function testPackageStructure()
    {
        $packagePath = '/tmp/extension.zip';
        
        if (!file_exists($packagePath)) {
            $this->fail("Cannot test structure: package not found");
            return;
        }
        
        // Extract to temp directory
        $tempDir = '/tmp/extension_test';
        if (is_dir($tempDir)) {
            system("rm -rf $tempDir");
        }
        mkdir($tempDir);
        
        $zip = new ZipArchive();
        if ($zip->open($packagePath) === true) {
            $zip->extractTo($tempDir);
            $zip->close();
            $this->pass("Package extracted successfully");
            
            // Check for manifest file
            $manifestFiles = glob($tempDir . '/*.xml');
            if (count($manifestFiles) > 0) {
                $this->pass("Manifest file found: " . basename($manifestFiles[0]));
                
                // Parse manifest
                $xml = simplexml_load_file($manifestFiles[0]);
                if ($xml) {
                    echo "  Extension type: " . (string)$xml['type'] . "\n";
                    echo "  Version: " . (string)$xml->version . "\n";
                    echo "  Author: " . (string)$xml->author . "\n";
                    $this->pass("Manifest is valid XML");
                } else {
                    $this->fail("Manifest is not valid XML");
                }
            } else {
                $this->fail("No manifest file found in package");
            }
            
            // Cleanup
            system("rm -rf $tempDir");
        } else {
            $this->fail("Failed to open package as ZIP");
        }
    }
    
    private function testJoomlaEnvironment()
    {
        define('_JEXEC', 1);
        define('JPATH_BASE', '/var/www/html');
        
        // Check if Joomla is installed
        if (file_exists(JPATH_BASE . '/configuration.php')) {
            $this->pass("Joomla configuration file exists");
            $this->joomlaInstalled = true;
            
            // Try to load Joomla
            if (file_exists(JPATH_BASE . '/includes/defines.php') && 
                file_exists(JPATH_BASE . '/includes/framework.php')) {
                $this->pass("Joomla framework files exist");
            } else {
                $this->fail("Joomla framework files missing");
                $this->joomlaInstalled = false;
            }
        } else {
            echo "  ⚠️  Joomla not installed (configuration.php missing)\n";
            $this->joomlaInstalled = false;
        }
    }
    
    private function testInstallExtension()
    {
        require_once JPATH_BASE . '/includes/defines.php';
        require_once JPATH_BASE . '/includes/framework.php';
        
        use Joomla\CMS\Installer\Installer;
        
        $packagePath = '/tmp/extension.zip';
        
        if (!file_exists($packagePath)) {
            $this->fail("Cannot install: package not found");
            return;
        }
        
        try {
            $installer = Installer::getInstance();
            
            if ($installer->install($packagePath)) {
                $this->pass("Extension installed successfully");
            } else {
                $this->fail("Extension installation failed");
                $errors = $installer->getErrors();
                foreach ($errors as $error) {
                    echo "  Error: $error\n";
                }
            }
        } catch (Exception $e) {
            $this->fail("Installation error: " . $e->getMessage());
        }
    }
    
    private function testComponentRegistered()
    {
        require_once JPATH_BASE . '/includes/defines.php';
        require_once JPATH_BASE . '/includes/framework.php';
        
        use Joomla\CMS\Factory;
        
        try {
            $db = Factory::getDbo();
            
            $query = $db->getQuery(true)
                ->select('*')
                ->from($db->quoteName('#__extensions'))
                ->where($db->quoteName('element') . ' = ' . $db->quote('com_j2commerce_importexport'))
                ->where($db->quoteName('type') . ' = ' . $db->quote('component'));
            
            $db->setQuery($query);
            $extension = $db->loadObject();
            
            if ($extension) {
                $this->pass("Component registered in database");
                echo "  Extension ID: {$extension->extension_id}\n";
                echo "  Name: {$extension->name}\n";
                echo "  Enabled: " . ($extension->enabled ? 'Yes' : 'No') . "\n";
                
                if (!$extension->enabled) {
                    $this->fail("Component is not enabled");
                }
            } else {
                $this->fail("Component not found in extensions table");
            }
        } catch (Exception $e) {
            $this->fail("Database error: " . $e->getMessage());
        }
    }
    
    private function pass($message)
    {
        $this->results[] = ['status' => 'PASS', 'message' => $message];
        echo "✅ PASS: $message\n";
    }
    
    private function fail($message)
    {
        $this->results[] = ['status' => 'FAIL', 'message' => $message];
        echo "❌ FAIL: $message\n";
    }
    
    private function printResults()
    {
        echo "\n=== Test Results ===\n";
        $passed = count(array_filter($this->results, fn($r) => $r['status'] === 'PASS'));
        $failed = count(array_filter($this->results, fn($r) => $r['status'] === 'FAIL'));
        
        echo "Passed: $passed\n";
        echo "Failed: $failed\n";
        echo "Total: " . count($this->results) . "\n";
    }
    
    private function allTestsPassed()
    {
        foreach ($this->results as $result) {
            if ($result['status'] === 'FAIL') {
                return false;
            }
        }
        return true;
    }
}

try {
    $test = new InstallationTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
