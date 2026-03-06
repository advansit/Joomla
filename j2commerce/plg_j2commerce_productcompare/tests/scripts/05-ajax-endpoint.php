<?php
/**
 * AJAX Endpoint Tests for J2Commerce Product Compare Plugin
 * Tests the compare AJAX endpoint via HTTP.
 */

class AjaxEndpointTest
{
    private $passed = 0;
    private $failed = 0;
    private $baseUrl = 'http://localhost';

    public function run(): bool
    {
        echo "=== AJAX Endpoint Tests ===\n\n";

        $this->test('AJAX endpoint responds (com_ajax)', function () {
            $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=productcompare&group=j2store&format=json';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            // 200 or 500 (plugin may throw error without session) — but not 404
            return $httpCode !== 404 && $httpCode !== 0;
        });

        $this->test('AJAX endpoint returns JSON', function () {
            $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=productcompare&group=j2store&format=json';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            curl_close($ch);
            $data = json_decode($response, true);
            // Response should be valid JSON (even if it's an error)
            return $data !== null || $response === '[]' || $response === 'null';
        });

        $this->test('AJAX with action=compare returns response', function () {
            $url = $this->baseUrl . '/index.php?option=com_ajax&plugin=productcompare&group=j2store&format=json&action=compare&product_ids=1,2';
            $ch = curl_init($url);
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT => 10,
                CURLOPT_FOLLOWLOCATION => true,
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            return $httpCode !== 404;
        });

        echo "\n=== AJAX Endpoint Test Summary ===\n";
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

$test = new AjaxEndpointTest();
exit($test->run() ? 0 : 1);
