<?php
/**
 * @package     J2Store Cleanup
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH
 * @license     Proprietary
 * @version     1.0.0
 */

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Installer\Installer;
use Joomla\CMS\Session\Session;
use Joomla\CMS\Language\Text;

$app = Factory::getApplication();
$db = Factory::getContainer()->get('DatabaseDriver');
$task = $app->input->get('task', 'display');

/**
 * Check if a J2Store extension is incompatible with J2Commerce 4.x
 * 
 * Inverted logic: an extension is compatible ONLY if it matches a known-good
 * pattern. Everything else is treated as legacy/incompatible.
 * No whitelist needed — Advans and J2Commerce extensions are recognized
 * by their manifest metadata (authorUrl, authorEmail, version).
 * 
 * Compatible if ANY of these is true:
 * - Core component (com_j2store) or this tool (com_j2store_cleanup)
 * - authorUrl contains 'advans.ch' (Advans-developed)
 * - authorEmail contains '@advans.ch' (Advans-developed)
 * - authorUrl contains 'j2commerce.com' (J2Commerce 4.x)
 * - authorEmail contains '@j2commerce.com' (J2Commerce 4.x)
 * 
 * @param object $manifest  Decoded manifest_cache from #__extensions
 * @param object $ext       Extension record from database
 * @return array ['incompatible' => bool, 'reason' => string]
 */
function checkJ2StoreCompatibility($manifest, $ext) {
    // Core component and this tool — always keep
    if ($ext->element === 'com_j2store' || $ext->element === 'com_j2store_cleanup') {
        return ['incompatible' => false, 'reason' => ''];
    }

    // Invalid manifest — cannot verify, treat as incompatible
    if (!is_object($manifest)) {
        return ['incompatible' => true, 'reason' => 'Invalid manifest data'];
    }

    $authorUrl = $manifest->authorUrl ?? '';
    $authorEmail = $manifest->authorEmail ?? '';

    // Advans-developed extensions
    if (stripos($authorUrl, 'advans.ch') !== false || stripos($authorEmail, '@advans.ch') !== false) {
        return ['incompatible' => false, 'reason' => ''];
    }

    // J2Commerce 4.x extensions
    if (stripos($authorUrl, 'j2commerce.com') !== false || stripos($authorEmail, '@j2commerce.com') !== false) {
        return ['incompatible' => false, 'reason' => ''];
    }

    // Everything else is legacy/incompatible — build reason string
    $version = $manifest->version ?? 'Unknown';
    $reasons = [];

    if ($version !== 'Unknown' && version_compare($version, '4.0.0', '<')) {
        $reasons[] = 'Version ' . $version . ' < 4.0.0';
    }
    if (stripos($authorUrl, 'j2store.org') !== false) {
        $reasons[] = 'Legacy (j2store.org)';
    }
    if (stripos($authorEmail, '@j2store.org') !== false) {
        $reasons[] = 'Legacy (@j2store.org)';
    }
    if (empty($reasons)) {
        $reasons[] = 'Unknown author — not from Advans or J2Commerce';
    }

    return ['incompatible' => true, 'reason' => implode(', ', $reasons)];
}

// Handle cleanup action
if ($task === 'cleanup' && Session::checkToken()) {
    $cids = $app->input->get('cid', [], 'array');
    $cids = array_map('intval', $cids);
    $cids = array_filter($cids); // Remove zeros
    
    if (empty($cids)) {
        $app->enqueueMessage('Please select at least one extension to remove.', 'warning');
        $app->redirect('index.php?option=com_j2store_cleanup');
        return;
    }
    
    $successCount = 0;
    $warningCount = 0;
    $errorCount = 0;
    $messages = [];
    
    // Get extension details for proper uninstall
    $query = $db->getQuery(true)
        ->select('extension_id, type, element, folder, client_id')
        ->from('#__extensions')
        ->where('extension_id IN (' . implode(',', $cids) . ')');
    $db->setQuery($query);
    $extensionsToRemove = $db->loadObjectList();
    
    foreach ($extensionsToRemove as $ext) {
        try {
            // Use Joomla's Installer for proper uninstallation
            $installer = Installer::getInstance();
            
            if ($installer->uninstall($ext->type, $ext->extension_id)) {
                $successCount++;
            } else {
                // Fallback: direct DB delete if uninstall fails
                $deleteQuery = $db->getQuery(true)
                    ->delete('#__extensions')
                    ->where('extension_id = ' . (int)$ext->extension_id);
                $db->setQuery($deleteQuery);
                $db->execute();
                $warningCount++;
                $messages[] = $ext->element . ' (DB only - files may remain)';
            }
        } catch (Exception $e) {
            $errorCount++;
            $messages[] = $ext->element . ': ' . $e->getMessage();
        }
    }
    
    if ($successCount > 0) {
        $app->enqueueMessage('Successfully removed ' . $successCount . ' extension(s).', 'success');
    }
    if ($warningCount > 0) {
        $app->enqueueMessage('Partially removed ' . $warningCount . ' extension(s): ' . implode(', ', array_slice($messages, 0, $warningCount)), 'warning');
    }
    if ($errorCount > 0) {
        $app->enqueueMessage('Failed to remove ' . $errorCount . ' extension(s): ' . implode(', ', array_slice($messages, $warningCount)), 'error');
    }
    
    $app->redirect('index.php?option=com_j2store_cleanup');
}

// Get all J2Store/J2Commerce extensions
$query = $db->getQuery(true)
    ->select('extension_id, name, type, element, folder, enabled, client_id, manifest_cache')
    ->from('#__extensions')
    ->where("(element LIKE '%j2store%' OR element LIKE '%j2commerce%' OR element LIKE 'j2%' OR element LIKE 'mod\_j2%' OR element LIKE 'com\_j2%' OR folder = 'j2store')")
    ->order('type, name');

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
            margin-bottom: 10px; 
            color: #fff;
            font-size: 28px;
            text-shadow: 0 0 10px rgba(0, 123, 255, 0.5);
        }
        .j2cleanup h2 {
            color: #8ec5fc;
            font-size: 18px;
            margin: 25px 0 15px 0;
            padding-bottom: 8px;
            border-bottom: 1px solid #333;
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
        .j2cleanup .alert-danger {
            background: #3d1a1a;
            border: 1px solid #8d2c2c;
            color: #fc8e8e;
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
        .j2cleanup .compatible {
            background: #1a3d1a !important;
            border-left: 3px solid #28a745;
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
            margin-right: 10px;
        }
        .j2cleanup .btn:hover { 
            background: #c82333; 
            box-shadow: 0 0 15px rgba(220, 53, 69, 0.5);
        }
        .j2cleanup .btn-secondary {
            background: #6c757d;
        }
        .j2cleanup .btn-secondary:hover {
            background: #5a6268;
            box-shadow: 0 0 15px rgba(108, 117, 125, 0.5);
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
        .j2cleanup .detection-info {
            background: #1a1a2e;
            border: 1px solid #333;
            border-radius: 4px;
            padding: 15px;
            margin: 20px 0;
        }
        .j2cleanup .detection-info h3 {
            color: #8ec5fc;
            margin: 0 0 10px 0;
            font-size: 16px;
        }
        .j2cleanup .detection-info ul {
            margin: 0;
            padding-left: 20px;
        }
        .j2cleanup .detection-info li {
            margin: 5px 0;
            color: #ccc;
        }
        .j2cleanup .detection-info .criterion {
            color: #4ade80;
            font-weight: bold;
        }
        .j2cleanup .badge {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
            font-weight: bold;
        }
        .j2cleanup .badge-danger {
            background: #dc3545;
            color: #fff;
        }
        .j2cleanup .badge-success {
            background: #28a745;
            color: #fff;
        }
        .j2cleanup .badge-warning {
            background: #ffc107;
            color: #000;
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
    <h1>J2Store Extension Cleanup</h1>
    <p style="color: #888; margin-bottom: 20px;">Migration tool for transitioning from J2Store to J2Commerce 4.x</p>
    
    <div class="detection-info">
        <h3>Detection Logic</h3>
        <p style="margin-bottom: 10px;">An extension is <strong>compatible</strong> only if it matches a known-good author:</p>
        <ul>
            <li><span class="criterion">authorUrl/Email contains "advans.ch"</span> — Advans-developed extension</li>
            <li><span class="criterion">authorUrl/Email contains "j2commerce.com"</span> — Official J2Commerce 4.x extension</li>
            <li><span class="criterion">com_j2store / com_j2store_cleanup</span> — Core component and this tool</li>
        </ul>
        <p style="margin-top: 10px;">Everything else is treated as <strong>legacy/incompatible</strong>. No whitelist needed — new Advans or J2Commerce extensions are automatically recognized by their manifest metadata.</p>
    </div>
    
    <?php if (empty($extensions)): ?>
        <div class="alert alert-success">
            <strong>No J2Store/J2Commerce extensions found!</strong>
            <p style="margin: 10px 0 0 0;">Your installation has no J2Store-related extensions.</p>
        </div>
    <?php else: ?>
        <div class="alert alert-warning">
            <strong>Warning:</strong> Always create a full backup (Akeeba Backup) before removing extensions!
            Removal uses Joomla's uninstaller which also deletes files and runs uninstall scripts.
        </div>
        
        <form action="index.php?option=com_j2store_cleanup" method="post">
            <?php
            // Separate extensions into compatible and incompatible
            $incompatible = [];
            $compatible = [];
            
            foreach ($extensions as $ext) {
                $manifest = json_decode($ext->manifest_cache);
                $result = checkJ2StoreCompatibility($manifest, $ext);
                $ext->_incompatible = $result['incompatible'];
                $ext->_reason = $result['reason'];
                $ext->_manifest = $manifest;
                
                if ($result['incompatible']) {
                    $incompatible[] = $ext;
                } else {
                    $compatible[] = $ext;
                }
            }
            ?>
            
            <?php if (!empty($incompatible)): ?>
            <h2>Incompatible Extensions (<?php echo count($incompatible); ?>)</h2>
            <p>These extensions need to be removed or upgraded to J2Commerce 4.x versions.</p>
            
            <table>
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" onclick="this.closest('table').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = this.checked)"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Reason</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($incompatible as $ext): 
                        $version = $ext->_manifest->version ?? 'Unknown';
                        $enabled = $ext->enabled ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-warning">Disabled</span>';
                    ?>
                    <tr class="incompatible">
                        <td><input type="checkbox" name="cid[]" value="<?php echo $ext->extension_id; ?>"></td>
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><span class="badge badge-danger"><?php echo htmlspecialchars($version); ?></span></td>
                        <td><?php echo $enabled; ?></td>
                        <td><strong><?php echo htmlspecialchars($ext->_reason); ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <input type="hidden" name="task" value="cleanup">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn" onclick="return confirm('Remove selected extensions?\n\nThis will:\n- Run uninstall scripts\n- Delete extension files\n- Remove database entries\n\nThis cannot be undone!')">
                Remove Selected Extensions
            </button>
            <?php endif; ?>
            
            <?php if (!empty($compatible)): ?>
            <h2>Compatible Extensions (<?php echo count($compatible); ?>)</h2>
            <p>These extensions are compatible with J2Commerce 4.x or are protected system extensions.</p>
            
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Version</th>
                        <th>Status</th>
                        <th>Author</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($compatible as $ext): 
                        $version = $ext->_manifest->version ?? 'Unknown';
                        $author = $ext->_manifest->author ?? 'Unknown';
                        $enabled = $ext->enabled ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-warning">Disabled</span>';
                    ?>
                    <tr class="compatible">
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><span class="badge badge-success"><?php echo htmlspecialchars($version); ?></span></td>
                        <td><?php echo $enabled; ?></td>
                        <td><?php echo htmlspecialchars($author); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (empty($incompatible)): ?>
            <div class="alert alert-success">
                <strong>All extensions are compatible!</strong>
                <p style="margin: 10px 0 0 0;">No incompatible J2Store extensions found. Your installation is ready for J2Commerce 4.x.</p>
            </div>
            <?php endif; ?>
        </form>
    <?php endif; ?>
    
    <div class="j2cleanup-footer">
        <div class="logo">Advans IT Solutions GmbH</div>
        <p>
            <strong>J2Store Extension Cleanup</strong> v1.1.0<br>
            Developed by <a href="https://advans.ch" target="_blank">Advans IT Solutions GmbH</a>
        </p>
        <p style="margin-top: 15px; font-size: 12px; color: #666;">
            © 2025 Advans IT Solutions GmbH. All rights reserved.
        </p>
    </div>
</div>
</body>
</html>
