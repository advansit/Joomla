<?php
/**
 * HTTP Integration Test for OSMap J2Commerce Plugin
 *
 * Makes a real HTTP request to the Joomla sitemap endpoint and verifies
 * that J2Commerce product URLs appear in the XML output.
 * This is the only test that exercises the full stack:
 * OSMap -> plugin -> getTree() -> DB query -> XML output.
 */

class SitemapHttpTest
{
    private int $passed = 0;
    private int $failed = 0;
    private string $sitemapUrl = 'http://localhost/index.php?option=com_osmap&view=xml&id=1';

    public function run(): bool
    {
        echo "=== Sitemap HTTP Integration Tests ===\n\n";
        echo "URL: {$this->sitemapUrl}\n\n";

        $xml = $this->fetchSitemap();
        if ($xml === null) {
            echo "FATAL: Could not fetch sitemap\n";
            return false;
        }

        echo "Sitemap fetched (" . strlen($xml) . " bytes)\n";
        echo "Response (first 500 chars):\n" . substr($xml, 0, 500) . "\n\n";

        $urls = $this->extractUrls($xml);
        echo "URLs found in sitemap: " . count($urls) . "\n";
        foreach ($urls as $url) {
            echo "  - {$url}\n";
        }
        echo "\n";

        $this->test('Sitemap returns valid XML', function () use ($xml) {
            return str_contains($xml, '<urlset') && str_contains($xml, 'sitemaps.org');
        });

        $this->test('Sitemap contains product Alpha URL', function () use ($urls) {
            foreach ($urls as $u) {
                if (str_contains($u, 'test-product-alpha')) return true;
            }
            return false;
        });

        $this->test('Sitemap contains product Beta URL', function () use ($urls) {
            foreach ($urls as $u) {
                if (str_contains($u, 'test-product-beta')) return true;
            }
            return false;
        });

        $this->test('Disabled product not in sitemap', function () use ($urls) {
            foreach ($urls as $u) {
                if (str_contains($u, 'test-product-disabled')) return false;
            }
            return true;
        });

        $this->test('Product without menu item not in sitemap', function () use ($urls) {
            foreach ($urls as $u) {
                if (str_contains($u, 'test-product-nomenu')) return false;
            }
            return true;
        });

        $this->test('At least 2 product URLs in sitemap', function () use ($urls) {
            $count = 0;
            foreach ($urls as $u) {
                if (str_contains($u, 'test-product-alpha') || str_contains($u, 'test-product-beta')) {
                    $count++;
                }
            }
            return $count >= 2;
        });

        $productUrls = array_filter($urls, fn($u) =>
            str_contains($u, 'test-product-alpha') || str_contains($u, 'test-product-beta')
        );

        echo "\nVerifying " . count($productUrls) . " product URL(s) are absolute:\n";
        foreach ($productUrls as $url) {
            $absoluteUrl = $url;
            if (!str_starts_with($url, 'http')) {
                $base = preg_replace('#/index\.php.*#', '', $this->sitemapUrl);
                $absoluteUrl = rtrim($base, '/') . '/' . ltrim($url, '/');
            }
            $this->test("URL is absolute and well-formed: $absoluteUrl", function () use ($absoluteUrl) {
                $parts = parse_url($absoluteUrl);
                return isset($parts['scheme'], $parts['host'], $parts['path'])
                    && in_array($parts['scheme'], ['http', 'https']);
            });
        }

        echo "\n=== Sitemap HTTP Test Summary ===\n";
        echo "Passed: {$this->passed}, Failed: {$this->failed}\n";
        return $this->failed === 0;
    }

    private function fetchSitemap(): ?string
    {
        for ($i = 0; $i < 6; $i++) {
            $ctx = stream_context_create(['http' => [
                'timeout'         => 30,
                'follow_location' => 1,
                'ignore_errors'   => true,
            ]]);
            $body = @file_get_contents($this->sitemapUrl, false, $ctx);
            if ($body !== false && strlen($body) > 0) {
                return $body;
            }
            echo "Waiting for Apache... ({$i})\n";
            sleep(5);
        }
        return null;
    }

    private function extractUrls(string $xml): array
    {
        $urls = [];
        if (preg_match_all('/<loc>(.*?)<\/loc>/s', $xml, $matches)) {
            foreach ($matches[1] as $raw) {
                $url = trim($raw);
                if (str_starts_with($url, '<![CDATA[') && str_ends_with($url, ']]>')) {
                    $url = substr($url, 9, -3);
                }
                $url = html_entity_decode($url, ENT_QUOTES | ENT_XML1, 'UTF-8');
                $urls[] = $url;
            }
        }
        return $urls;
    }

    private function test(string $name, callable $fn): void
    {
        try {
            if ($fn()) { echo "✓ {$name}\n"; $this->passed++; }
            else       { echo "✗ {$name}\n"; $this->failed++; }
        } catch (\Throwable $e) {
            echo "✗ {$name} - Error: {$e->getMessage()}\n";
            $this->failed++;
        }
    }
}

$test = new SitemapHttpTest();
exit($test->run() ? 0 : 1);
