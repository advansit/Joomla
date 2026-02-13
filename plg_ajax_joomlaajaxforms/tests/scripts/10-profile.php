<?php
/**
 * Test 10: Profile
 * Tests the profile save functionality
 */

define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
$_SERVER['HTTP_HOST'] = $_SERVER['HTTP_HOST'] ?? 'localhost';
$_SERVER['SCRIPT_NAME'] = $_SERVER['SCRIPT_NAME'] ?? '/index.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class ProfileTest
{
    public function run(): bool
    {
        echo "=== Profile Tests ===\n\n";

        $allPassed = true;
        $allPassed = $this->testProfileFeatureConfig() && $allPassed;
        $allPassed = $this->testHandleSaveProfileMethodExists() && $allPassed;
        $allPassed = $this->testProfileLanguageStrings() && $allPassed;

        echo "\n=== Profile Test Summary ===\n";
        return $allPassed;
    }

    private function testProfileFeatureConfig(): bool
    {
        echo "Test: Profile feature config exists... ";

        $db = Factory::getDbo();
        $query = $db->getQuery(true)
            ->select('params')
            ->from($db->quoteName('#__extensions'))
            ->where($db->quoteName('type') . ' = ' . $db->quote('plugin'))
            ->where($db->quoteName('folder') . ' = ' . $db->quote('ajax'))
            ->where($db->quoteName('element') . ' = ' . $db->quote('joomlaajaxforms'));

        $db->setQuery($query);
        $params = json_decode($db->loadResult() ?: '{}', true);

        // enable_profile should default to 1
        $enabled = $params['enable_profile'] ?? 1;
        if ($enabled == 1) {
            echo "PASS\n";
            return true;
        }

        echo "FAIL (enable_profile=$enabled)\n";
        return false;
    }

    private function testHandleSaveProfileMethodExists(): bool
    {
        echo "Test: handleSaveProfile method exists... ";

        $file = JPATH_ROOT . '/plugins/ajax/joomlaajaxforms/src/Extension/JoomlaAjaxForms.php';
        if (!file_exists($file)) {
            echo "FAIL (file not found)\n";
            return false;
        }

        $content = file_get_contents($file);
        if (strpos($content, 'function handleSaveProfile') !== false) {
            echo "PASS\n";
            return true;
        }

        echo "FAIL (method not found in source)\n";
        return false;
    }

    private function testProfileLanguageStrings(): bool
    {
        echo "Test: Profile language strings loaded... ";

        $lang = Factory::getLanguage();
        $lang->load('plg_ajax_joomlaajaxforms', JPATH_ADMINISTRATOR);

        $keys = [
            'PLG_AJAX_JOOMLAAJAXFORMS_PROFILE_SAVED',
            'PLG_AJAX_JOOMLAAJAXFORMS_PROFILE_SAVE_ERROR',
            'PLG_AJAX_JOOMLAAJAXFORMS_PASSWORD_TOO_SHORT',
            'PLG_AJAX_JOOMLAAJAXFORMS_PROFILE_TITLE',
        ];

        $missing = [];
        foreach ($keys as $key) {
            if ($lang->hasKey($key) === false) {
                $missing[] = $key;
            }
        }

        if (empty($missing)) {
            echo "PASS\n";
            return true;
        }

        echo "FAIL (missing: " . implode(', ', $missing) . ")\n";
        return false;
    }
}

$test = new ProfileTest();
$result = $test->run();
exit($result ? 0 : 1);
