<?php
/**
 * @package     J2Commerce Import/Export Component
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Component\J2CommerceImportExport\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;

class ImportModel extends BaseDatabaseModel
{
    /**
     * Batch size for processing
     */
    const BATCH_SIZE = 100;

    /**
     * Preview file contents
     *
     * @param   string  $filePath  Path to file
     * @param   int     $limit     Number of rows to preview
     * @return  array   Preview data
     * @since   1.0.0
     */
    public function previewFile(string $filePath, int $limit = 10): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        
        switch (strtolower($extension)) {
            case 'csv':
                return $this->previewCSV($filePath, $limit);
            case 'json':
                return $this->previewJSON($filePath, $limit);
            case 'xml':
                return $this->previewXML($filePath, $limit);
            default:
                throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_UNSUPPORTED_FORMAT'));
        }
    }

    /**
     * Import data from file
     *
     * @param   string  $filePath  Path to file
     * @param   string  $type      Import type (products, variants, etc.)
     * @param   array   $mapping   Field mapping
     * @param   array   $options   Import options
     * @return  array   Import results
     * @since   1.0.0
     */
    public function importData(string $filePath, string $type, array $mapping, array $options = []): array
    {
        $extension = pathinfo($filePath, PATHINFO_EXTENSION);
        $data = $this->parseFile($filePath, $extension);
        
        $results = [
            'total' => count($data),
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        // Process in batches
        $batches = array_chunk($data, self::BATCH_SIZE);
        
        foreach ($batches as $batch) {
            $batchResults = $this->processBatch($batch, $type, $mapping, $options);
            $results['imported'] += $batchResults['imported'];
            $results['updated'] += $batchResults['updated'];
            $results['failed'] += $batchResults['failed'];
            $results['errors'] = array_merge($results['errors'], $batchResults['errors']);
        }
        
        return $results;
    }

    /**
     * Process a batch of import data
     *
     * @param   array   $batch     Batch data
     * @param   string  $type      Import type
     * @param   array   $mapping   Field mapping
     * @param   array   $options   Import options
     * @return  array   Batch results
     * @since   1.0.0
     */
    protected function processBatch(array $batch, string $type, array $mapping, array $options): array
    {
        $results = [
            'imported' => 0,
            'updated' => 0,
            'failed' => 0,
            'errors' => []
        ];
        
        foreach ($batch as $index => $row) {
            try {
                $mappedRow = $this->mapFields($row, $mapping);
                $result = $this->importRow($type, $mappedRow, $options);
                
                if ($result === 'inserted') {
                    $results['imported']++;
                } elseif ($result === 'updated') {
                    $results['updated']++;
                }
                
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = sprintf(
                    'Row %d: %s',
                    $index + 1,
                    $e->getMessage()
                );
            }
        }
        
        return $results;
    }

    /**
     * Import a single row
     *
     * @param   string  $type     Import type
     * @param   array   $data     Row data
     * @param   array   $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importRow(string $type, array $data, array $options): string
    {
        switch ($type) {
            case 'products':
                return $this->importProduct($data, $options);
            case 'variants':
                return $this->importVariant($data, $options);
            case 'prices':
                return $this->importPrice($data, $options);
            case 'options':
                return $this->importOption($data, $options);
            case 'optionvalues':
                return $this->importOptionValue($data, $options);
            case 'images':
                return $this->importImage($data, $options);
            case 'filters':
                return $this->importFilter($data, $options);
            default:
                throw new \Exception(Text::sprintf('COM_J2COMMERCE_IMPORTEXPORT_ERROR_INVALID_TYPE', $type));
        }
    }

    /**
     * Import product
     *
     * @param   array  $data     Product data
     * @param   array  $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importProduct(array $data, array $options): string
    {
        $db = $this->getDatabase();
        
        // Check if product exists
        $productId = $data['j2store_product_id'] ?? null;
        $existingId = null;
        
        if ($productId) {
            $query = $db->getQuery(true)
                ->select('j2store_product_id')
                ->from($db->quoteName('#__j2store_products'))
                ->where($db->quoteName('j2store_product_id') . ' = :productid')
                ->bind(':productid', $productId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $existingId = $db->loadResult();
        }
        
        // Prepare product data
        $product = (object)[
            'product_source' => $data['product_source'] ?? 'com_content',
            'product_source_id' => $data['product_source_id'] ?? 0,
            'product_type' => $data['product_type'] ?? 'simple',
            'visibility' => $data['visibility'] ?? 'both',
            'enabled' => $data['enabled'] ?? 1,
            'taxprofile_id' => $data['taxprofile_id'] ?? 0,
            'vendor_id' => $data['vendor_id'] ?? 0,
            'manufacturer_id' => $data['manufacturer_id'] ?? 0,
            'created_on' => $data['created_on'] ?? Factory::getDate()->toSql(),
            'modified_on' => Factory::getDate()->toSql(),
        ];
        
        if ($existingId && ($options['update_existing'] ?? true)) {
            // Update existing product
            $product->j2store_product_id = $existingId;
            $db->updateObject('#__j2store_products', $product, 'j2store_product_id');
            return 'updated';
        } else {
            // Insert new product
            $db->insertObject('#__j2store_products', $product);
            return 'inserted';
        }
    }

    /**
     * Import variant
     *
     * @param   array  $data     Variant data
     * @param   array  $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importVariant(array $data, array $options): string
    {
        $db = $this->getDatabase();
        
        // Check if variant exists by SKU
        $sku = $data['sku'] ?? null;
        $existingId = null;
        
        if ($sku) {
            $query = $db->getQuery(true)
                ->select('j2store_variant_id')
                ->from($db->quoteName('#__j2store_variants'))
                ->where($db->quoteName('sku') . ' = :sku')
                ->bind(':sku', $sku);
            
            $db->setQuery($query);
            $existingId = $db->loadResult();
        }
        
        // Prepare variant data
        $variant = (object)[
            'product_id' => $data['product_id'] ?? 0,
            'sku' => $sku,
            'upc' => $data['upc'] ?? '',
            'price' => $data['price'] ?? 0,
            'stock' => $data['stock'] ?? 0,
            'weight' => $data['weight'] ?? 0,
            'length' => $data['length'] ?? 0,
            'width' => $data['width'] ?? 0,
            'height' => $data['height'] ?? 0,
            'availability' => $data['availability'] ?? 1,
            'is_master' => $data['is_master'] ?? 0,
            'created_on' => $data['created_on'] ?? Factory::getDate()->toSql(),
            'modified_on' => Factory::getDate()->toSql(),
        ];
        
        if ($existingId && ($options['update_existing'] ?? true)) {
            $variant->j2store_variant_id = $existingId;
            $db->updateObject('#__j2store_variants', $variant, 'j2store_variant_id');
            return 'updated';
        } else {
            $db->insertObject('#__j2store_variants', $variant);
            return 'inserted';
        }
    }

    /**
     * Import price
     *
     * @param   array  $data     Price data
     * @param   array  $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importPrice(array $data, array $options): string
    {
        $db = $this->getDatabase();
        
        // Check if price exists
        $variantId = $data['variant_id'] ?? 0;
        $clientId = $data['client_id'] ?? 0;
        
        $query = $db->getQuery(true)
            ->select('j2store_productprice_id')
            ->from($db->quoteName('#__j2store_productprices'))
            ->where($db->quoteName('variant_id') . ' = :variantid')
            ->where($db->quoteName('client_id') . ' = :clientid')
            ->bind(':variantid', $variantId, \Joomla\Database\ParameterType::INTEGER)
            ->bind(':clientid', $clientId, \Joomla\Database\ParameterType::INTEGER);
        
        $db->setQuery($query);
        $existingId = $db->loadResult();
        
        // Prepare price data
        $price = (object)[
            'variant_id' => $variantId,
            'client_id' => $clientId,
            'price' => $data['price'] ?? 0,
            'price_type' => $data['price_type'] ?? 'standard',
            'quantity_from' => $data['quantity_from'] ?? 0,
            'quantity_to' => $data['quantity_to'] ?? 0,
            'date_from' => $data['date_from'] ?? '0000-00-00 00:00:00',
            'date_to' => $data['date_to'] ?? '0000-00-00 00:00:00',
        ];
        
        if ($existingId && ($options['update_existing'] ?? true)) {
            $price->j2store_productprice_id = $existingId;
            $db->updateObject('#__j2store_productprices', $price, 'j2store_productprice_id');
            return 'updated';
        } else {
            $db->insertObject('#__j2store_productprices', $price);
            return 'inserted';
        }
    }

    /**
     * Import product option
     *
     * @param   array  $data     Option data
     * @param   array  $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importOption(array $data, array $options): string
    {
        $db = $this->getDatabase();
        
        // Check if option exists
        $optionId = $data['j2store_option_id'] ?? null;
        $existingId = null;
        
        if ($optionId) {
            $query = $db->getQuery(true)
                ->select('j2store_option_id')
                ->from($db->quoteName('#__j2store_options'))
                ->where($db->quoteName('j2store_option_id') . ' = :optionid')
                ->bind(':optionid', $optionId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $existingId = $db->loadResult();
        }
        
        // Prepare option data
        $option = (object)[
            'option_name' => $data['option_name'] ?? '',
            'option_type' => $data['option_type'] ?? 'select',
            'option_required' => $data['option_required'] ?? 0,
            'ordering' => $data['ordering'] ?? 0,
            'enabled' => $data['enabled'] ?? 1,
        ];
        
        if ($existingId && ($options['update_existing'] ?? true)) {
            $option->j2store_option_id = $existingId;
            $db->updateObject('#__j2store_options', $option, 'j2store_option_id');
            return 'updated';
        } else {
            $db->insertObject('#__j2store_options', $option);
            return 'inserted';
        }
    }

    /**
     * Import option value
     *
     * @param   array  $data     Option value data
     * @param   array  $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importOptionValue(array $data, array $options): string
    {
        $db = $this->getDatabase();
        
        // Check if option value exists
        $valueId = $data['j2store_optionvalue_id'] ?? null;
        $existingId = null;
        
        if ($valueId) {
            $query = $db->getQuery(true)
                ->select('j2store_optionvalue_id')
                ->from($db->quoteName('#__j2store_optionvalues'))
                ->where($db->quoteName('j2store_optionvalue_id') . ' = :valueid')
                ->bind(':valueid', $valueId, \Joomla\Database\ParameterType::INTEGER);
            
            $db->setQuery($query);
            $existingId = $db->loadResult();
        }
        
        // Prepare option value data
        $optionValue = (object)[
            'option_id' => $data['option_id'] ?? 0,
            'optionvalue_name' => $data['optionvalue_name'] ?? '',
            'optionvalue_price' => $data['optionvalue_price'] ?? 0,
            'optionvalue_prefix' => $data['optionvalue_prefix'] ?? '+',
            'ordering' => $data['ordering'] ?? 0,
            'enabled' => $data['enabled'] ?? 1,
        ];
        
        if ($existingId && ($options['update_existing'] ?? true)) {
            $optionValue->j2store_optionvalue_id = $existingId;
            $db->updateObject('#__j2store_optionvalues', $optionValue, 'j2store_optionvalue_id');
            return 'updated';
        } else {
            $db->insertObject('#__j2store_optionvalues', $optionValue);
            return 'inserted';
        }
    }

    /**
     * Import product image
     *
     * @param   array  $data     Image data
     * @param   array  $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importImage(array $data, array $options): string
    {
        $db = $this->getDatabase();
        
        // Prepare image data
        $image = (object)[
            'product_id' => $data['product_id'] ?? 0,
            'image_path' => $data['image_path'] ?? '',
            'image_type' => $data['image_type'] ?? 'main',
            'ordering' => $data['ordering'] ?? 0,
        ];
        
        $db->insertObject('#__j2store_productimages', $image);
        return 'inserted';
    }

    /**
     * Import product filter
     *
     * @param   array  $data     Filter data
     * @param   array  $options  Import options
     * @return  string  'inserted' or 'updated'
     * @since   1.0.0
     */
    protected function importFilter(array $data, array $options): string
    {
        $db = $this->getDatabase();
        
        // Prepare filter data
        $filter = (object)[
            'product_id' => $data['product_id'] ?? 0,
            'filter_id' => $data['filter_id'] ?? 0,
            'filter_value' => $data['filter_value'] ?? '',
        ];
        
        $db->insertObject('#__j2store_product_filters', $filter);
        return 'inserted';
    }

    /**
     * Preview CSV file
     */
    protected function previewCSV(string $filePath, int $limit): array
    {
        $config = Factory::getConfig();
        $delimiter = $config->get('csv_delimiter', ',');
        $enclosure = $config->get('csv_enclosure', '"');

        if (!file_exists($filePath)) {
            throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_FILE_NOT_FOUND'));
        }

        $handle = fopen($filePath, 'r');
        if (!$handle) {
            throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_FILE_OPEN'));
        }

        $headers = fgetcsv($handle, 0, $delimiter, $enclosure);
        
        $rows = [];
        $count = 0;
        
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false && $count < $limit) {
            if (count($row) === count($headers)) {
                $rows[] = array_combine($headers, $row);
                $count++;
            }
        }
        
        fclose($handle);
        
        return [
            'headers' => $headers,
            'rows' => $rows,
            'total' => $count
        ];
    }

    /**
     * Preview JSON file
     */
    protected function previewJSON(string $filePath, int $limit): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_FILE_NOT_FOUND'));
        }

        $content = file_get_contents($filePath);
        $data = json_decode($content, true);
        
        if (!is_array($data)) {
            throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_INVALID_JSON'));
        }
        
        $preview = array_slice($data, 0, $limit);
        $headers = !empty($preview) ? array_keys($preview[0]) : [];
        
        return [
            'headers' => $headers,
            'rows' => $preview,
            'total' => count($data)
        ];
    }

    /**
     * Preview XML file
     */
    protected function previewXML(string $filePath, int $limit): array
    {
        if (!file_exists($filePath)) {
            throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_FILE_NOT_FOUND'));
        }

        $xml = simplexml_load_file($filePath);
        if (!$xml) {
            throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_INVALID_XML'));
        }

        $data = json_decode(json_encode($xml), true);
        
        if (!isset($data['item'])) {
            throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_INVALID_XML_STRUCTURE'));
        }
        
        $items = $data['item'];
        if (!isset($items[0])) {
            $items = [$items];
        }
        
        $preview = array_slice($items, 0, $limit);
        $headers = !empty($preview) ? array_keys($preview[0]) : [];
        
        return [
            'headers' => $headers,
            'rows' => $preview,
            'total' => count($items)
        ];
    }

    /**
     * Parse file based on extension
     */
    protected function parseFile(string $filePath, string $extension): array
    {
        switch (strtolower($extension)) {
            case 'csv':
                return $this->parseCSV($filePath);
            case 'json':
                return $this->parseJSON($filePath);
            case 'xml':
                return $this->parseXML($filePath);
            default:
                throw new \Exception(Text::_('COM_J2COMMERCE_IMPORTEXPORT_ERROR_UNSUPPORTED_FORMAT'));
        }
    }

    /**
     * Parse CSV file
     */
    protected function parseCSV(string $filePath): array
    {
        $config = Factory::getConfig();
        $delimiter = $config->get('csv_delimiter', ',');
        $enclosure = $config->get('csv_enclosure', '"');

        $handle = fopen($filePath, 'r');
        $headers = fgetcsv($handle, 0, $delimiter, $enclosure);
        
        $data = [];
        while (($row = fgetcsv($handle, 0, $delimiter, $enclosure)) !== false) {
            if (count($row) === count($headers)) {
                $data[] = array_combine($headers, $row);
            }
        }
        
        fclose($handle);
        return $data;
    }

    /**
     * Parse JSON file
     */
    protected function parseJSON(string $filePath): array
    {
        $content = file_get_contents($filePath);
        return json_decode($content, true) ?: [];
    }

    /**
     * Parse XML file
     */
    protected function parseXML(string $filePath): array
    {
        $xml = simplexml_load_file($filePath);
        $data = json_decode(json_encode($xml), true);
        
        $items = $data['item'] ?? [];
        if (!isset($items[0])) {
            $items = [$items];
        }
        
        return $items;
    }

    /**
     * Map fields from source to target
     */
    protected function mapFields(array $row, array $mapping): array
    {
        $mapped = [];
        foreach ($mapping as $source => $target) {
            if (isset($row[$source])) {
                $mapped[$target] = $row[$source];
            }
        }
        return $mapped;
    }
}
