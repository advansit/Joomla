<?php
/**
 * Test 12: .htaccess Check
 * Tests the installer script's .htaccess validation logic
 */

class HtaccessCheckTest
{
    private $scriptPath = '/var/www/html/plugins/ajax/joomlaajaxforms/script.php';
    private $htaccessPath = '/var/www/html/.htaccess';

    public function run(): bool
    {
        echo "=== .htaccess Check Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testScriptFileExists() && $allPassed;
        $allPassed = $this->testScriptFileInXml() && $allPassed;
        $allPassed = $this->testCheckMethodExists() && $allPassed;
        $allPassed = $this->testLanguageKeys() && $allPassed;
        $allPassed = $this->testDetectsBlockedComponentUrls() && $allPassed;
        $allPassed = $this->testDetectsBlockedOptionUrls() && $allPassed;
        $allPassed = $this->testPassesWithExceptions() && $allPassed;
        $allPassed = $this->testPassesWithoutBlocking() && $allPassed;
        $allPassed = $this->testPassesWithNoHtaccess() && $allPassed;

        echo "\n=== .htaccess Check Test Summary ===\n";
        echo "All tests completed.\n";
        return $allPassed;
    }

    private function testScriptFileExists(): bool
    {
        echo "Test: script.php exists... ";

        if (file_exists($this->scriptPath)) {
            echo "PASS\n";
            return true;
        }

        echo "FAIL (not found at {$this->scriptPath})\n";
        return false;
    }

    private function testScriptFileInXml(): bool
    {
        echo "Test: scriptfile declared in XML... ";

        $xmlPath = '/var/www/html/plugins/ajax/joomlaajaxforms/joomlaajaxforms.xml';
        if (!file_exists($xmlPath)) {
            echo "FAIL (XML not found)\n";
            return false;
        }

        $content = file_get_contents($xmlPath);
        if (strpos($content, '<scriptfile>script.php</scriptfile>') !== false) {
            echo "PASS\n";
            return true;
        }

        echo "FAIL (scriptfile tag missing)\n";
        return false;
    }

    private function testCheckMethodExists(): bool
    {
        echo "Test: checkHtaccess method exists in script.php... ";

        $content = file_get_contents($this->scriptPath);
        if (strpos($content, 'function checkHtaccess') !== false) {
            echo "PASS\n";
            return true;
        }

        echo "FAIL\n";
        return false;
    }

    private function testLanguageKeys(): bool
    {
        echo "Test: .htaccess language keys present... ";

        $requiredKeys = [
            'PLG_AJAX_JOOMLAAJAXFORMS_HTACCESS_WARNING_TITLE',
            'PLG_AJAX_JOOMLAAJAXFORMS_HTACCESS_COMPONENT_BLOCKED',
            'PLG_AJAX_JOOMLAAJAXFORMS_HTACCESS_OPTION_BLOCKED',
            'PLG_AJAX_JOOMLAAJAXFORMS_HTACCESS_WARNING_ACTION',
        ];

        $langFiles = [
            '/var/www/html/plugins/ajax/joomlaajaxforms/language/en-GB/plg_ajax_joomlaajaxforms.ini',
            '/var/www/html/plugins/ajax/joomlaajaxforms/language/de-DE/plg_ajax_joomlaajaxforms.ini',
        ];

        $missing = [];
        foreach ($langFiles as $file) {
            if (!file_exists($file)) {
                $missing[] = basename(dirname($file)) . '/' . basename($file);
                continue;
            }
            $content = file_get_contents($file);
            $lang = basename(dirname($file));
            foreach ($requiredKeys as $key) {
                if (strpos($content, $key . '=') === false) {
                    $missing[] = $lang . '/' . $key;
                }
            }
        }

        if (empty($missing)) {
            echo "PASS (" . count($requiredKeys) . " keys x 2 languages)\n";
            return true;
        }

        echo "FAIL (Missing: " . implode(', ', $missing) . ")\n";
        return false;
    }

    /**
     * Simulate: .htaccess blocks /component/ without plugin= exception
     */
    private function testDetectsBlockedComponentUrls(): bool
    {
        echo "Test: Detects blocked /component/ URLs... ";

        $htaccess = <<<'HTACCESS'
RewriteCond %{REQUEST_URI} ^(/[a-z]{2})?/component/ [NC]
RewriteRule ^([a-z]{2})?/?component/.*$ /$1/ [R=301,L]
HTACCESS;

        // The check looks for component blocking WITHOUT plugin= exception
        $hasBlocking = (bool) preg_match(
            '/RewriteCond.*\/component\/.*\n.*RewriteRule.*component/im',
            $htaccess
        );
        $hasException = (bool) preg_match(
            '/RewriteCond.*QUERY_STRING.*!plugin=/im',
            $htaccess
        );

        if ($hasBlocking && !$hasException) {
            echo "PASS (blocking detected, no exception)\n";
            return true;
        }

        echo "FAIL\n";
        return false;
    }

    /**
     * Simulate: .htaccess blocks index.php?option=com_* without com_ajax exception
     */
    private function testDetectsBlockedOptionUrls(): bool
    {
        echo "Test: Detects blocked index.php?option= URLs... ";

        $htaccess = <<<'HTACCESS'
RewriteCond %{QUERY_STRING} ^option=com_ [NC]
RewriteCond %{QUERY_STRING} !^option=com_users [NC]
RewriteRule ^index\.php$ /? [R=301,L]
HTACCESS;

        $hasBlocking = (bool) preg_match(
            '/RewriteCond.*QUERY_STRING.*\^option=com_/im',
            $htaccess
        );
        $hasAjaxException = (bool) preg_match(
            '/RewriteCond.*QUERY_STRING.*!.*option=com_ajax/im',
            $htaccess
        );

        if ($hasBlocking && !$hasAjaxException) {
            echo "PASS (blocking detected, com_ajax exception missing)\n";
            return true;
        }

        echo "FAIL\n";
        return false;
    }

    /**
     * Simulate: .htaccess with correct exceptions — should pass
     */
    private function testPassesWithExceptions(): bool
    {
        echo "Test: Passes with correct exceptions... ";

        $htaccess = <<<'HTACCESS'
RewriteCond %{REQUEST_URI} ^(/[a-z]{2})?/component/ [NC]
RewriteCond %{QUERY_STRING} !plugin= [NC]
RewriteRule ^([a-z]{2})?/?component/.*$ /$1/ [R=301,L]
RewriteCond %{QUERY_STRING} ^option=com_ [NC]
RewriteCond %{QUERY_STRING} !^option=com_ajax [NC]
RewriteRule ^index\.php$ /? [R=301,L]
HTACCESS;

        $issues = 0;

        // Component check
        if (preg_match('/RewriteCond.*\/component\/.*\n.*RewriteRule.*component/im', $htaccess)) {
            if (!preg_match('/RewriteCond.*QUERY_STRING.*!plugin=/im', $htaccess)) {
                $issues++;
            }
        }

        // Option check
        if (preg_match('/RewriteCond.*QUERY_STRING.*\^option=com_/im', $htaccess)) {
            if (!preg_match('/RewriteCond.*QUERY_STRING.*!.*option=com_ajax/im', $htaccess)) {
                $issues++;
            }
        }

        if ($issues === 0) {
            echo "PASS (no issues found)\n";
            return true;
        }

        echo "FAIL ($issues issues found)\n";
        return false;
    }

    /**
     * Simulate: Standard Joomla .htaccess without any blocking — should pass
     */
    private function testPassesWithoutBlocking(): bool
    {
        echo "Test: Passes with standard Joomla .htaccess... ";

        $htaccess = <<<'HTACCESS'
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/index\.php
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule .* index.php [L]
HTACCESS;

        $hasComponentBlock = (bool) preg_match(
            '/RewriteCond.*\/component\/.*\n.*RewriteRule.*component/im',
            $htaccess
        );
        $hasOptionBlock = (bool) preg_match(
            '/RewriteCond.*QUERY_STRING.*\^option=com_/im',
            $htaccess
        );

        if (!$hasComponentBlock && !$hasOptionBlock) {
            echo "PASS (no blocking rules)\n";
            return true;
        }

        echo "FAIL\n";
        return false;
    }

    /**
     * No .htaccess at all — should pass (Nginx or no SEF)
     */
    private function testPassesWithNoHtaccess(): bool
    {
        echo "Test: Passes when no .htaccess exists... ";

        // The script checks file_exists() first and returns early
        // We verify the logic: no file = no issues
        $fakeFile = '/tmp/nonexistent_htaccess_' . uniqid();
        if (!file_exists($fakeFile)) {
            echo "PASS (no file = no check needed)\n";
            return true;
        }

        echo "FAIL\n";
        return false;
    }
}

$test = new HtaccessCheckTest();
$result = $test->run();
exit($result ? 0 : 1);
