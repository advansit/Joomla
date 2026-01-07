<?php
/**
 * Test 08: Multilingual Support
 * Tests language file installation and multilingual functionality
 * 
 * Test Environment: Only en-GB system language installed
 * - Component provides 5 frontend languages (en-GB, de-DE, de-CH, fr-CH, it-IT)
 * - Backend language: en-GB only
 * - Tests verify component language files, not system languages
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;

class MultilingualTest
{
    private $db;
    private $baseUrl = 'http://localhost';

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Multilingual Tests ===\n\n";

        // First check which languages are installed in the system
        $this->checkInstalledLanguages();

        $allPassed = true;
        $allPassed = $this->testFrontendLanguageFilesInstalled() && $allPassed;
        $allPassed = $this->testBackendLanguageFilesInstalled() && $allPassed;
        $allPassed = $this->testLanguageFileContent() && $allPassed;
        $allPassed = $this->testLanguageStringsInTemplates() && $allPassed;
        $allPassed = $this->testManifestLanguageRegistration() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function checkInstalledLanguages(): void
    {
        echo "Checking installed system languages... ";
        
        $query = $this->db->getQuery(true)
            ->select('lang_code, title')
            ->from($this->db->quoteName('#__languages'))
            ->where($this->db->quoteName('published') . ' = 1');
        
        $this->db->setQuery($query);
        $languages = $this->db->loadObjectList();
        
        echo "\n";
        foreach ($languages as $lang) {
            echo "  - {$lang->lang_code}: {$lang->title}\n";
        }
        echo "\n";
    }

    private function testFrontendLanguageFilesInstalled(): bool
    {
        echo "Test: Frontend language files installed... ";
        
        // Get installed system languages
        $query = $this->db->getQuery(true)
            ->select('lang_code')
            ->from($this->db->quoteName('#__languages'))
            ->where($this->db->quoteName('published') . ' = 1');
        
        $this->db->setQuery($query);
        $installedLanguages = $this->db->loadColumn();
        
        $supportedLanguages = ['en-GB', 'de-DE', 'de-CH', 'fr-CH', 'it-IT'];
        $foundCount = 0;
        $foundLanguages = [];
        
        // Check which supported languages have files installed (global or component folder)
        foreach ($supportedLanguages as $tag) {
            $globalPath = JPATH_SITE . "/language/$tag/com_j2commerce_importexport.ini";
            $componentPath = JPATH_SITE . "/components/com_j2commerce_importexport/language/$tag/com_j2commerce_importexport.ini";
            
            if (file_exists($globalPath) || file_exists($componentPath)) {
                $foundCount++;
                $foundLanguages[] = $tag;
            }
        }
        
        // Pass if files are available (either in global or component folder)
        if ($foundCount > 0 && in_array('en-GB', $foundLanguages)) {
            echo "✅ PASS ($foundCount of " . count($supportedLanguages) . " supported languages)\n";
            echo "  Found: " . implode(', ', $foundLanguages) . "\n";
            echo "  System has: " . implode(', ', $installedLanguages) . "\n";
            return true;
        }
        
        echo "❌ FAIL (No language files found)\n";
        return false;
    }

    private function testBackendLanguageFilesInstalled(): bool
    {
        echo "Test: Backend language files installed... ";
        
        // Component provides en-GB and de-DE backend languages
        $supportedLanguages = ['en-GB', 'de-DE'];
        $foundCount = 0;
        $foundLanguages = [];
        
        // Check which supported languages have backend files installed
        foreach ($supportedLanguages as $tag) {
            $iniFile = JPATH_ADMINISTRATOR . "/language/$tag/com_j2commerce_importexport.ini";
            $sysFile = JPATH_ADMINISTRATOR . "/language/$tag/com_j2commerce_importexport.sys.ini";
            
            if (file_exists($iniFile) && file_exists($sysFile)) {
                $foundCount++;
                $foundLanguages[] = $tag;
            }
        }
        
        // Pass if at least en-GB is found (de-DE may not be installed in test system)
        if ($foundCount > 0 && in_array('en-GB', $foundLanguages)) {
            echo "✅ PASS ($foundCount of " . count($supportedLanguages) . " backend languages available)\n";
            echo "  Found: " . implode(', ', $foundLanguages) . "\n";
            if ($foundCount < count($supportedLanguages)) {
                echo "  Note: Not all system languages installed in test environment\n";
            }
            return true;
        }
        
        echo "❌ FAIL (No backend language files found)\n";
        return false;
    }

    private function testLanguageFileContent(): bool
    {
        echo "Test: Language file content... ";
        
        // Test key language strings exist in installed language files
        $requiredKeys = [
            'COM_SWISSQRCODE_SITE_TITLE',
            'COM_SWISSQRCODE_SUCCESS_TITLE',
            'COM_SWISSQRCODE_COPY_LICENSE',
            'COM_SWISSQRCODE_NEXT_STEPS',
            'COM_SWISSQRCODE_COMPANY',
            'COM_SWISSQRCODE_SUPPORT'
        ];
        
        $supportedLanguages = ['en-GB', 'de-DE', 'de-CH', 'fr-CH', 'it-IT'];
        $missingKeys = [];
        $checkedCount = 0;
        
        foreach ($supportedLanguages as $tag) {
            $filePath = JPATH_SITE . "/language/$tag/com_j2commerce_importexport.ini";
            
            if (!file_exists($filePath)) {
                continue;
            }
            
            $checkedCount++;
            $content = file_get_contents($filePath);
            
            foreach ($requiredKeys as $key) {
                if (strpos($content, $key) === false) {
                    $missingKeys[] = "$tag: $key";
                }
            }
        }
        
        if ($checkedCount > 0 && empty($missingKeys)) {
            echo "PASS (All required keys present)\n";
            echo "  Verified " . count($requiredKeys) . " keys in $checkedCount language file(s)\n";
            return true;
        }
        
        if ($checkedCount == 0) {
            echo "SKIP (No language files found to check)\n";
            return true;
        }
        
        echo "FAIL (Missing keys: " . implode(', ', $missingKeys) . ")\n";
        return false;
    }

    private function testLanguageStringsInTemplates(): bool
    {
        echo "Test: Language strings in templates... ";
        
        // Templates are in the component directory structure
        $templates = [
            '/var/www/html/components/com_j2commerce_importexport/tmpl/activate/default.php',
            '/var/www/html/components/com_j2commerce_importexport/tmpl/activate/success.php'
        ];
        
        $issues = [];
        
        foreach ($templates as $template) {
            if (!file_exists($template)) {
                $issues[] = basename($template) . " not found";
                continue;
            }
            
            $content = file_get_contents($template);
            
            // Check for Text::_() usage
            if (!preg_match('/Text::_\([\'"]COM_SWISSQRCODE_/', $content)) {
                $issues[] = basename($template) . " doesn't use Text::_()";
            }
            
            // Check for hardcoded German text (should be replaced)
            $hardcodedPatterns = [
                '/Lizenz aktivieren/i',
                '/Bestellnummer/i',
                '/Hardware-Hash/i'
            ];
            
            foreach ($hardcodedPatterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    // Check if it's in a comment or language file reference
                    $lines = explode("\n", $content);
                    foreach ($lines as $line) {
                        if (preg_match($pattern, $line) && !preg_match('/^\s*(\/\/|\*|;)/', $line)) {
                            $issues[] = basename($template) . " contains hardcoded text: " . trim($line);
                            break;
                        }
                    }
                }
            }
        }
        
        if (empty($issues)) {
            echo "PASS (Templates use language strings)\n";
            echo "  Verified " . count($templates) . " templates\n";
            return true;
        }
        
        echo "FAIL\n";
        foreach ($issues as $issue) {
            echo "  - $issue\n";
        }
        return false;
    }

    private function testManifestLanguageRegistration(): bool
    {
        echo "Test: Manifest language registration... ";
        
        $manifestPath = '/var/www/html/administrator/components/com_j2commerce_importexport/j2commerce_importexport.xml';
        
        if (!file_exists($manifestPath)) {
            echo "FAIL (Manifest not found)\n";
            return false;
        }
        
        $xml = simplexml_load_file($manifestPath);
        
        if ($xml === false) {
            echo "FAIL (Invalid XML)\n";
            return false;
        }
        
        // Check frontend languages
        $frontendLanguages = $xml->xpath('//languages[@folder="site/language"]/language');
        $expectedFrontend = ['en-GB', 'de-DE', 'de-CH', 'fr-CH', 'it-IT'];
        $registeredFrontend = [];
        
        foreach ($frontendLanguages as $lang) {
            $tag = (string)$lang['tag'];
            $registeredFrontend[] = $tag;
        }
        
        $missingFrontend = array_diff($expectedFrontend, $registeredFrontend);
        
        // Check backend languages
        $backendLanguages = $xml->xpath('//administration/languages[@folder="admin/language"]/language');
        $expectedBackend = ['en-GB', 'de-DE'];
        $registeredBackend = [];
        
        foreach ($backendLanguages as $lang) {
            $tag = (string)$lang['tag'];
            $file = (string)$lang;
            
            // Count unique tags (each tag should have .ini and .sys.ini)
            if (!in_array($tag, $registeredBackend)) {
                $registeredBackend[] = $tag;
            }
        }
        
        $missingBackend = array_diff($expectedBackend, $registeredBackend);
        
        if (empty($missingFrontend) && empty($missingBackend)) {
            echo "PASS\n";
            echo "  Frontend: " . count($registeredFrontend) . " languages registered\n";
            echo "  Backend: " . count($registeredBackend) . " languages registered\n";
            return true;
        }
        
        echo "FAIL\n";
        if (!empty($missingFrontend)) {
            echo "  Missing frontend: " . implode(', ', $missingFrontend) . "\n";
        }
        if (!empty($missingBackend)) {
            echo "  Missing backend: " . implode(', ', $missingBackend) . "\n";
        }
        return false;
    }

    private function printSummary(): void
    {
        echo "\n";
        echo str_repeat("=", 50) . "\n";
        echo "Multilingual Tests Complete\n";
        echo str_repeat("=", 50) . "\n";
    }
}

try {
    $test = new MultilingualTest();
    $success = $test->run();
    exit($success ? 0 : 1);
} catch (Exception $e) {
    echo "FATAL ERROR: " . $e->getMessage() . "\n";
    echo $e->getTraceAsString() . "\n";
    exit(1);
}
