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

class DisplayController extends BaseController
{
    protected $default_view = 'dashboard';

    public function display($cachable = false, $urlparams = [])
    {
        return parent::display($cachable, $urlparams);
    }
}
