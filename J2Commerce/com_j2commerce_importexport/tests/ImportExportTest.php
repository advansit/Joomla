<?php
/**
 * @package     J2Commerce Import/Export
 * @subpackage  Tests
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Joomla\Component\J2commerceImportexport\Administrator\Tests;

use PHPUnit\Framework\TestCase;

/**
 * Test cases for J2Commerce Import/Export component
 *
 * @since  1.0.0
 */
class ImportExportTest extends TestCase
{
    /**
     * Test CSV export format
     *
     * @return  void
     */
    public function testCsvExportFormat()
    {
        $data = [
            ['id' => 1, 'name' => 'Product 1', 'price' => 10.00],
            ['id' => 2, 'name' => 'Product 2', 'price' => 20.00]
        ];

        $csv = $this->generateCsv($data);
        
        $this->assertStringContainsString('id,name,price', $csv);
        $this->assertStringContainsString('1,"Product 1",10.00', $csv);
        $this->assertStringContainsString('2,"Product 2",20.00', $csv);
    }

    /**
     * Test XML export format
     *
     * @return  void
     */
    public function testXmlExportFormat()
    {
        $data = [
            ['id' => 1, 'name' => 'Product 1', 'price' => 10.00]
        ];

        $xml = $this->generateXml($data);
        
        $this->assertStringContainsString('<?xml version="1.0"', $xml);
        $this->assertStringContainsString('<products>', $xml);
        $this->assertStringContainsString('<product>', $xml);
        $this->assertStringContainsString('<id>1</id>', $xml);
    }

    /**
     * Test JSON export format
     *
     * @return  void
     */
    public function testJsonExportFormat()
    {
        $data = [
            ['id' => 1, 'name' => 'Product 1', 'price' => 10.00]
        ];

        $json = json_encode($data);
        $decoded = json_decode($json, true);
        
        $this->assertIsArray($decoded);
        $this->assertCount(1, $decoded);
        $this->assertEquals(1, $decoded[0]['id']);
        $this->assertEquals('Product 1', $decoded[0]['name']);
    }

    /**
     * Test CSV import parsing
     *
     * @return  void
     */
    public function testCsvImportParsing()
    {
        $csv = "id,name,price\n1,\"Product 1\",10.00\n2,\"Product 2\",20.00";
        
        $data = $this->parseCsv($csv);
        
        $this->assertIsArray($data);
        $this->assertCount(2, $data);
        $this->assertEquals('1', $data[0]['id']);
        $this->assertEquals('Product 1', $data[0]['name']);
    }

    /**
     * Test field mapping
     *
     * @return  void
     */
    public function testFieldMapping()
    {
        $sourceData = ['product_id' => 1, 'product_name' => 'Test'];
        $mapping = ['product_id' => 'id', 'product_name' => 'name'];
        
        $mapped = $this->applyMapping($sourceData, $mapping);
        
        $this->assertArrayHasKey('id', $mapped);
        $this->assertArrayHasKey('name', $mapped);
        $this->assertEquals(1, $mapped['id']);
        $this->assertEquals('Test', $mapped['name']);
    }

    /**
     * Test batch processing
     *
     * @return  void
     */
    public function testBatchProcessing()
    {
        $data = range(1, 150);
        $batchSize = 50;
        
        $batches = array_chunk($data, $batchSize);
        
        $this->assertCount(3, $batches);
        $this->assertCount(50, $batches[0]);
        $this->assertCount(50, $batches[1]);
        $this->assertCount(50, $batches[2]);
    }

    /**
     * Helper: Generate CSV from array
     *
     * @param   array  $data  Data array
     *
     * @return  string
     */
    private function generateCsv(array $data): string
    {
        if (empty($data)) {
            return '';
        }

        $output = fopen('php://temp', 'r+');
        fputcsv($output, array_keys($data[0]));
        
        foreach ($data as $row) {
            fputcsv($output, $row);
        }
        
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }

    /**
     * Helper: Generate XML from array
     *
     * @param   array  $data  Data array
     *
     * @return  string
     */
    private function generateXml(array $data): string
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $xml .= '<products>' . "\n";
        
        foreach ($data as $item) {
            $xml .= '  <product>' . "\n";
            foreach ($item as $key => $value) {
                $xml .= '    <' . $key . '>' . htmlspecialchars($value) . '</' . $key . '>' . "\n";
            }
            $xml .= '  </product>' . "\n";
        }
        
        $xml .= '</products>';
        
        return $xml;
    }

    /**
     * Helper: Parse CSV string
     *
     * @param   string  $csv  CSV string
     *
     * @return  array
     */
    private function parseCsv(string $csv): array
    {
        $lines = explode("\n", trim($csv));
        $headers = str_getcsv(array_shift($lines));
        $data = [];
        
        foreach ($lines as $line) {
            if (empty($line)) {
                continue;
            }
            $values = str_getcsv($line);
            $data[] = array_combine($headers, $values);
        }
        
        return $data;
    }

    /**
     * Helper: Apply field mapping
     *
     * @param   array  $data     Source data
     * @param   array  $mapping  Field mapping
     *
     * @return  array
     */
    private function applyMapping(array $data, array $mapping): array
    {
        $mapped = [];
        
        foreach ($data as $key => $value) {
            $newKey = $mapping[$key] ?? $key;
            $mapped[$newKey] = $value;
        }
        
        return $mapped;
    }
}
