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

class ImportController extends BaseController
{
    public function upload()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        $input = $app->input;

        $file = $input->files->get('import_file');

        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            echo new JsonResponse(['error' => 'File upload failed'], '', true);
            $app->close();
        }

        $session = $app->getSession();
        $tmpPath = JPATH_ROOT . '/tmp/j2commerce_import_' . uniqid() . '_' . $file['name'];
        
        if (move_uploaded_file($file['tmp_name'], $tmpPath)) {
            $session->set('import_file', $tmpPath, 'j2commerce_import');
            echo new JsonResponse(['success' => true, 'file' => basename($tmpPath)]);
        } else {
            echo new JsonResponse(['error' => 'Failed to save file'], '', true);
        }

        $app->close();
    }

    public function preview()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        $session = $app->getSession();
        
        $filePath = $session->get('import_file', '', 'j2commerce_import');

        if (!file_exists($filePath)) {
            echo new JsonResponse(['error' => 'File not found'], '', true);
            $app->close();
        }

        try {
            $model = $this->getModel('Import', 'Administrator');
            $preview = $model->previewFile($filePath);
            
            echo new JsonResponse(['success' => true, 'data' => $preview]);
        } catch (\Exception $e) {
            echo new JsonResponse(['error' => $e->getMessage()], '', true);
        }

        $app->close();
    }

    public function process()
    {
        Session::checkToken() or jexit(Text::_('JINVALID_TOKEN'));
        
        $app = Factory::getApplication();
        $session = $app->getSession();
        $input = $app->input;
        
        $filePath = $session->get('import_file', '', 'j2commerce_import');
        $mapping = $input->get('mapping', [], 'array');
        $type = $input->get('type', 'products', 'string');

        if (!file_exists($filePath)) {
            echo new JsonResponse(['error' => 'File not found'], '', true);
            $app->close();
        }

        try {
            $model = $this->getModel('Import', 'Administrator');
            $result = $model->importData($filePath, $type, $mapping);
            
            // Clean up
            @unlink($filePath);
            $session->clear('import_file', 'j2commerce_import');
            
            echo new JsonResponse([
                'success' => true,
                'imported' => $result['imported'],
                'failed' => $result['failed'],
                'errors' => $result['errors']
            ]);
        } catch (\Exception $e) {
            echo new JsonResponse(['error' => $e->getMessage()], '', true);
        }

        $app->close();
    }
}
