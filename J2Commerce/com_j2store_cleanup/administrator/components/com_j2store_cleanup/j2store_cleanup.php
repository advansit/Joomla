<?php
/**
 * @package     J2Store Cleanup
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Session\Session;

$app = Factory::getApplication();
$db = Factory::getDbo();
$task = $app->input->get('task', 'display');

// Handle cleanup action
if ($task === 'cleanup' && Session::checkToken()) {
    $cids = $app->input->get('cid', [], 'array');
    $cids = array_map('intval', $cids);
    
    if (!empty($cids)) {
        try {
            // Delete extensions directly (no backup)
            $query = $db->getQuery(true)
                ->delete('#__extensions')
                ->where('extension_id IN (' . implode(',', $cids) . ')');
            $db->setQuery($query);
            $db->execute();
            
            $app->enqueueMessage('Successfully removed ' . count($cids) . ' extension(s).', 'success');
        } catch (Exception $e) {
            $app->enqueueMessage('Error: ' . $e->getMessage(), 'error');
        }
    }
    
    $app->redirect('index.php?option=com_j2store_cleanup');
}

// Get all J2Store extensions
$query = $db->getQuery(true)
    ->select('extension_id, name, type, element, folder, enabled, manifest_cache')
    ->from('#__extensions')
    ->where("element LIKE '%j2store%' OR element LIKE '%j2commerce%'")
    ->order('name');

$db->setQuery($query);
$extensions = $db->loadObjectList();

?>
<!DOCTYPE html>
<html style="background: #000 !important;">
<head>
    <style>
        html, body, #wrapper, #content, .com_j2store_cleanup {
            background: #000 !important;
            margin: 0;
            padding: 0;
        }
        body {
            background: #000 !important;
        }
        .j2cleanup { 
            padding: 20px; 
            background: #000;
            color: #e0e0e0;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif;
            min-height: 100vh;
        }
        .j2cleanup h1 { 
            margin-bottom: 20px; 
            color: #fff;
            font-size: 28px;
            text-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }
        .j2cleanup .alert { 
            padding: 15px; 
            margin: 15px 0; 
            border-radius: 4px; 
        }
        .j2cleanup .alert-info { 
            background: #1a3a52; 
            border: 1px solid #2c5f8d; 
            color: #8ec5fc; 
        }
        .j2cleanup .alert-warning { 
            background: #4a3c1a; 
            border: 1px solid #8d6e2c; 
            color: #ffc107; 
        }
        .j2cleanup .alert-success { 
            background: #1a4d2e; 
            border: 1px solid #2c8d5f; 
            color: #4ade80; 
        }
        .j2cleanup table { 
            width: 100%; 
            border-collapse: collapse; 
            margin: 20px 0; 
            background: #1a1a1a;
            border: 1px solid #333;
        }
        .j2cleanup th, .j2cleanup td { 
            padding: 10px; 
            text-align: left; 
            border: 1px solid #333; 
            color: #e0e0e0;
        }
        .j2cleanup th { 
            background: #007bff; 
            color: #fff !important;
            font-weight: bold;
        }
        .j2cleanup tr:nth-child(even) { 
            background: #0d0d0d; 
        }
        .j2cleanup tr:hover { 
            background: #2a2a2a; 
        }
        .j2cleanup .incompatible { 
            background: #3d1a1a !important; 
            border-left: 3px solid #dc3545;
        }
        .j2cleanup .btn { 
            padding: 10px 20px; 
            background: #dc3545; 
            color: #fff !important; 
            border: none; 
            border-radius: 4px; 
            cursor: pointer; 
            font-size: 16px;
            font-weight: bold;
            transition: all 0.3s;
        }
        .j2cleanup .btn:hover { 
            background: #c82333; 
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.5);
        }
        .j2cleanup code {
            background: #2a2a2a;
            padding: 2px 6px;
            border-radius: 3px;
            color: #8ec5fc;
            font-family: monospace;
            border: 1px solid #333;
        }
        .j2cleanup strong {
            color: #fff;
        }
        .j2cleanup p {
            color: #e0e0e0;
        }
        .j2cleanup-footer {
            margin-top: 40px;
            padding: 20px;
            border-top: 2px solid #333;
            text-align: center;
            color: #888;
        }
        .j2cleanup-footer a {
            color: #007bff;
            text-decoration: none;
            transition: color 0.3s;
        }
        .j2cleanup-footer a:hover {
            color: #0056b3;
            text-decoration: underline;
        }
        .j2cleanup-footer .logo {
            font-size: 20px;
            font-weight: bold;
            color: #007bff;
            margin-bottom: 10px;
        }
    </style>
</head>
<body>
<div class="j2cleanup">
    <h1>🧹 J2Store Extension Cleanup</h1>
    
    <div class="alert alert-info">
        <strong>ℹ️ Info:</strong> This tool identifies incompatible J2Store extensions (disabled or old versions).
    </div>
    
    <?php if (empty($extensions)): ?>
        <div class="alert alert-info">
            <strong>✅ No J2Store extensions found!</strong>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <strong>⚠️ Warning:</strong> Make sure you have a full backup before removing extensions!
        </div>
        
        <form action="index.php?option=com_j2store_cleanup" method="post">
            <table>
                <thead>
                    <tr>
                        <th width="20"><input type="checkbox" onclick="Joomla.checkAll(this)"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $incompatibleCount = 0;
                    foreach ($extensions as $i => $ext): 
                        $manifest = json_decode($ext->manifest_cache);
                        $version = $manifest->version ?? 'Unknown';
                        $enabled = $ext->enabled ? 'Enabled' : 'Disabled';
                        
                        $isIncompatible = false;
                        $reason = '';
                        
                        // Only mark as incompatible if disabled
                        // Don't check version for modules/plugins (they have different versioning)
                        if ($ext->enabled == 0) {
                            $isIncompatible = true;
                            $reason = 'Disabled';
                            $incompatibleCount++;
                        } elseif ($ext->type === 'component' && $version !== 'Unknown' && version_compare($version, '4.0.0', '<')) {
                            // Only check component versions
                            // Exclude com_j2store (core) and our cleanup tool
                            if ($ext->element !== 'com_j2store' && $ext->element !== 'com_j2store_cleanup') {
                                $isIncompatible = true;
                                $reason = 'Old component version (< 4.0.0)';
                                $incompatibleCount++;
                            }
                        }
                        
                        $rowClass = $isIncompatible ? 'incompatible' : '';
                    ?>
                    <tr class="<?php echo $rowClass; ?>">
                        <td>
                            <?php if ($isIncompatible): ?>
                                <input type="checkbox" name="cid[]" value="<?php echo $ext->extension_id; ?>">
                            <?php endif; ?>
                        </td>
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><?php echo htmlspecialchars($version); ?></td>
                        <td><?php echo $enabled; ?></td>
                        <td><strong><?php echo $reason; ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <?php if ($incompatibleCount > 0): ?>
                <p><strong>Found <?php echo $incompatibleCount; ?> incompatible extension(s)</strong> (highlighted in red)</p>
                <input type="hidden" name="task" value="cleanup">
                <?php echo HTMLHelper::_('form.token'); ?>
                <button type="submit" class="btn" onclick="return confirm('Remove selected extensions? This cannot be undone!')">
                    🗑️ Remove Selected Extensions
                </button>
            <?php else: ?>
                <div class="alert alert-success">
                    <strong>✅ All J2Store extensions are compatible!</strong>
                    <p style="margin: 10px 0 0 0;">No incompatible extensions found. Your J2Store installation is clean.</p>
                </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
    
    <div class="j2cleanup-footer">
        <div class="logo">Advans IT Solutions GmbH</div>
        <p>
            <strong>J2Store Extension Cleanup</strong> v1.0.0<br>
            Developed by <a href="https://advans.ch" target="_blank">Advans IT Solutions GmbH</a>
        </p>
        <p style="margin-top: 15px; font-size: 12px; color: #666;">
            © 2025 Advans IT Solutions GmbH. All rights reserved.
        </p>
    </div>
</div>
</body>
</html>
