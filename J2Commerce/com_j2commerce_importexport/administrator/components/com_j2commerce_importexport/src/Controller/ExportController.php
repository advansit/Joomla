<?php
/**
 * @package     J2Commerce Import/Export Component
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

namespace Advans\Component\J2CommerceImportExport\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Factory;
use Joomla\CMS\Response\JsonResponse;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

class ExportController extends BaseController
{
    public function export()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        $input = $app->input;

        $type = $input->get('type', 'products', 'string');
        $format = $input->get('format', 'csv', 'string');

        try {
            $model = $this->getModel('Export', 'Administrator');
            $data = $model->exportData($type);
            
            $filename = $type . '_' . date('Y-m-d_H-i-s') . '.' . $format;
            
            switch ($format) {
                case 'csv':
                    $this->exportCSV($data, $filename);
                    break;
                case 'xml':
                    $this->exportXML($data, $filename);
                    break;
                case 'json':
                    $this->exportJSON($data, $filename);
                    break;
            }
        } catch (\Exception $e) {
            echo new JsonResponse(['error' => $e->getMessage()], '', true);
        }

        $app->close();
    }

    protected function exportCSV($data, $filename)
    {
        $app = Factory::getApplication();
        $params = $app->getParams();
        
        $delimiter = $params->get('csv_delimiter', ',');
        $enclosure = $params->get('csv_enclosure', '"');

        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $output = fopen('php://output', 'w');

        if (!empty($data)) {
            fputcsv($output, array_keys($data[0]), $delimiter, $enclosure);
            foreach ($data as $row) {
                fputcsv($output, $row, $delimiter, $enclosure);
            }
        }

        fclose($output);
    }

    protected function exportXML($data, $filename)
    {
        header('Content-Type: text/xml');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><export></export>');
        
        foreach ($data as $row) {
            $item = $xml->addChild('item');
            foreach ($row as $key => $value) {
                $item->addChild($key, htmlspecialchars($value));
            }
        }

        echo $xml->asXML();
    }

    protected function exportJSON($data, $filename)
    {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="' . $filename . '"');

        echo json_encode($data, JSON_PRETTY_PRINT);
    }
}
