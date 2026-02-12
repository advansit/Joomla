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
 * Classify a J2Store/J2Commerce extension's migration status.
 * 
 * Returns a status for display purposes. The user decides what to remove.
 * 
 * Statuses:
 * - 'core'    — J2Store/J2Commerce core component, cannot be removed here
 * - 'updated' — Updated for J2Commerce 4.x (authorUrl/Email j2commerce.com)
 * - 'legacy'  — Old J2Store extension (j2store.org + version < 4.0)
 * - 'review'  — Unknown compatibility, user should verify
 * 
 * @param object $manifest  Decoded manifest_cache from #__extensions
 * @param object $ext       Extension record from database
 * @return array ['status' => string, 'reason' => string]
 */
function classifyExtension($manifest, $ext) {
    // Core component — never remove via this tool
    if ($ext->element === 'com_j2store') {
        $version = is_object($manifest) ? ($manifest->version ?? '?') : '?';
        return ['status' => 'core', 'reason' => 'Core component (v' . $version . ')'];
    }

    if (!is_object($manifest)) {
        return ['status' => 'review', 'reason' => 'Invalid manifest — verify manually'];
    }

    $authorUrl   = $manifest->authorUrl ?? '';
    $authorEmail = $manifest->authorEmail ?? '';
    $version     = $manifest->version ?? 'Unknown';

    // J2Commerce 4.x official extensions
    if (stripos($authorUrl, 'j2commerce.com') !== false || stripos($authorEmail, '@j2commerce.com') !== false) {
        return ['status' => 'updated', 'reason' => 'J2Commerce 4.x (v' . $version . ')'];
    }

    // Legacy J2Store: j2store.org author AND version < 4.0
    $isJ2StoreOrg = stripos($authorUrl, 'j2store.org') !== false || stripos($authorEmail, '@j2store.org') !== false;

    if ($isJ2StoreOrg && $version !== 'Unknown' && version_compare($version, '4.0.0', '<')) {
        return ['status' => 'legacy', 'reason' => 'J2Store legacy (v' . $version . ')'];
    }

    // j2store.org but version >= 4.0 — might have been updated
    if ($isJ2StoreOrg) {
        return ['status' => 'review', 'reason' => 'j2store.org author but v' . $version . ' — verify compatibility'];
    }

    // Third-party or custom extension
    $author = $manifest->author ?? 'Unknown';
    return ['status' => 'review', 'reason' => 'Third-party (' . $author . ', v' . $version . ') — verify compatibility'];
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
        .j2cleanup .badge-info {
            background: #17a2b8;
            color: #fff;
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
        <h3>How extensions are classified</h3>
        <ul>
            <li><span class="badge badge-success">Updated</span> — authorUrl or authorEmail points to <strong>j2commerce.com</strong></li>
            <li><span class="badge badge-danger">Legacy</span> — Author is <strong>j2store.org</strong> and version &lt; 4.0.0</li>
            <li><span class="badge badge-warning">Review</span> — Cannot determine automatically. Check with the extension vendor.</li>
            <li><span class="badge badge-info">Core</span> — J2Store/J2Commerce core component</li>
        </ul>
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
            $groups = ['legacy' => [], 'review' => [], 'updated' => [], 'core' => []];
            
            foreach ($extensions as $ext) {
                $manifest = json_decode($ext->manifest_cache);
                $result = classifyExtension($manifest, $ext);
                $ext->_status = $result['status'];
                $ext->_reason = $result['reason'];
                $ext->_manifest = $manifest;
                $groups[$result['status']][] = $ext;
            }
            ?>
            
            <?php if (!empty($groups['legacy'])): ?>
            <h2>Legacy Extensions (<?php echo count($groups['legacy']); ?>)</h2>
            <p>Old J2Store extensions — likely incompatible with J2Commerce 4.x. Select and remove what you no longer need.</p>
            
            <table>
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" onclick="this.closest('table').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = this.checked)"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Version</th>
                        <th>Enabled</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups['legacy'] as $ext): 
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
                        <td><?php echo htmlspecialchars($ext->_reason); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($groups['review'])): ?>
            <h2>Needs Review (<?php echo count($groups['review']); ?>)</h2>
            <p>Compatibility could not be determined automatically. Check with the vendor before removing.</p>
            
            <table>
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" onclick="this.closest('table').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = this.checked)"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Version</th>
                        <th>Enabled</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups['review'] as $ext): 
                        $version = $ext->_manifest->version ?? 'Unknown';
                        $enabled = $ext->enabled ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-warning">Disabled</span>';
                    ?>
                    <tr style="background: #3d3a1a !important; border-left: 3px solid #ffc107;">
                        <td><input type="checkbox" name="cid[]" value="<?php echo $ext->extension_id; ?>"></td>
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><span class="badge badge-warning"><?php echo htmlspecialchars($version); ?></span></td>
                        <td><?php echo $enabled; ?></td>
                        <td><?php echo htmlspecialchars($ext->_reason); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($groups['legacy']) || !empty($groups['review'])): ?>
            <input type="hidden" name="task" value="cleanup">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn" onclick="return confirm('Remove selected extensions?\n\nThis will:\n- Run uninstall scripts\n- Delete extension files\n- Remove database entries\n\nThis cannot be undone!')">
                Remove Selected Extensions
            </button>
            <?php endif; ?>
            
            <?php if (!empty($groups['updated']) || !empty($groups['core'])): ?>
            <h2>Compatible Extensions (<?php echo count($groups['updated']) + count($groups['core']); ?>)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Version</th>
                        <th>Enabled</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_merge($groups['core'], $groups['updated']) as $ext): 
                        $version = $ext->_manifest->version ?? 'Unknown';
                        $author = $ext->_manifest->author ?? 'Unknown';
                        $enabled = $ext->enabled ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-warning">Disabled</span>';
                        $badge = $ext->_status === 'core' ? 'badge-info' : 'badge-success';
                    ?>
                    <tr class="compatible">
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($version); ?></span></td>
                        <td><?php echo $enabled; ?></td>
                        <td><?php echo htmlspecialchars($ext->_reason); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (empty($groups['legacy']) && empty($groups['review'])): ?>
            <div class="alert alert-success">
                <strong>No legacy extensions found!</strong>
                <p style="margin: 10px 0 0 0;">All J2Store extensions appear to be compatible with J2Commerce 4.x.</p>
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
