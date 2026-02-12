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
 * Resolve the filesystem path for an extension.
 *
 * @param object $ext  Extension record from #__extensions
 * @return string|null  Absolute path or null if not found
 */
function getExtensionPath($ext) {
    $base = ($ext->client_id == 1) ? JPATH_ADMINISTRATOR : JPATH_SITE;

    switch ($ext->type) {
        case 'component':
            return $base . '/components/' . $ext->element;
        case 'module':
            return $base . '/modules/' . $ext->element;
        case 'plugin':
            return JPATH_PLUGINS . '/' . $ext->folder . '/' . $ext->element;
        case 'template':
            return $base . '/templates/' . $ext->element;
        case 'library':
            return JPATH_LIBRARIES . '/' . $ext->element;
        case 'file':
            return null; // language packs etc. — no single path
    }
    return null;
}

/**
 * Scan PHP files in a directory for deprecated/incompatible patterns.
 *
 * @param string $path  Directory to scan
 * @return array  List of issues found, each with 'type' and 'detail'
 */
function scanForIssues($path) {
    if (!is_dir($path)) {
        return [];
    }

    $issues = [];
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($path, RecursiveDirectoryIterator::SKIP_DOTS)
    );

    // Joomla compatibility patterns
    $joomlaPatterns = [
        '/\bJFactory\b/'                        => 'JFactory (removed in Joomla 5)',
        '/\bJPlugin\b/'                         => 'JPlugin (removed in Joomla 4)',
        '/\bJModel(Legacy)?\b/'                 => 'JModel/JModelLegacy (removed in Joomla 4)',
        '/\bJTable\b/'                          => 'JTable (removed in Joomla 4)',
        '/\bJView(Legacy)?\b/'                  => 'JView/JViewLegacy (removed in Joomla 4)',
        '/\bJController(Legacy)?\b/'            => 'JController/JControllerLegacy (removed in Joomla 4)',
        '/\bJForm\b/'                           => 'JForm (removed in Joomla 4)',
        '/\bJText\b/'                           => 'JText (removed in Joomla 5)',
        '/\bJHtml\b/'                           => 'JHtml (removed in Joomla 5)',
        '/\bJRoute\b/'                          => 'JRoute (removed in Joomla 5)',
        '/\bJUri\b/'                            => 'JUri (removed in Joomla 5)',
        '/\bJSession\b/'                        => 'JSession (removed in Joomla 5)',
        '/Factory::getUser\s*\(/'               => 'Factory::getUser() (deprecated in Joomla 5)',
        '/Factory::getDbo\s*\(/'                => 'Factory::getDbo() (deprecated in Joomla 5)',
        '/Factory::getSession\s*\(/'            => 'Factory::getSession() (deprecated in Joomla 5)',
        '/Factory::getDocument\s*\(/'           => 'Factory::getDocument() (deprecated in Joomla 5)',
    ];

    // J2Commerce compatibility patterns
    $j2Patterns = [
        '/com_j2store\/library\//'              => 'Old J2Store library path (removed in J2Commerce 4)',
        '/\bF0FModel\b/'                        => 'F0FModel (removed in J2Commerce 4)',
        '/\bF0FTable\b/'                        => 'F0FTable (removed in J2Commerce 4)',
        '/\bF0FController\b/'                   => 'F0FController (removed in J2Commerce 4)',
        '/\bF0F\\\\\b/'                         => 'F0F namespace (removed in J2Commerce 4)',
        '/require_once.*com_j2store.*plugins/'  => 'Old J2Store plugin loader (removed in J2Commerce 4)',
    ];

    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') {
            continue;
        }

        $content = @file_get_contents($file->getPathname());
        if ($content === false) {
            continue;
        }

        // Strip comments to reduce false positives
        $contentNoComments = preg_replace('#/\*.*?\*/#s', '', $content);
        $contentNoComments = preg_replace('#//.*$#m', '', $contentNoComments);

        foreach ($joomlaPatterns as $pattern => $label) {
            if (preg_match($pattern, $contentNoComments)) {
                $issues[] = ['type' => 'joomla', 'detail' => $label];
            }
        }

        foreach ($j2Patterns as $pattern => $label) {
            if (preg_match($pattern, $contentNoComments)) {
                $issues[] = ['type' => 'j2store', 'detail' => $label];
            }
        }
    }

    // Deduplicate
    $seen = [];
    $unique = [];
    foreach ($issues as $issue) {
        $key = $issue['type'] . ':' . $issue['detail'];
        if (!isset($seen[$key])) {
            $seen[$key] = true;
            $unique[] = $issue;
        }
    }

    return $unique;
}

/**
 * Classify a J2Store/J2Commerce extension's migration status.
 *
 * Scans the actual extension files for deprecated Joomla and J2Store APIs.
 * No whitelist — compatibility is determined by what the code actually uses.
 *
 * Statuses:
 * - 'core'         — J2Store/J2Commerce core component
 * - 'compatible'   — No issues found in code scan
 * - 'incompatible' — Deprecated APIs found (with details)
 * - 'no-files'     — Extension path not found or no PHP files
 *
 * @param object $manifest  Decoded manifest_cache from #__extensions
 * @param object $ext       Extension record from database
 * @return array ['status' => string, 'reason' => string, 'issues' => array]
 */
function classifyExtension($manifest, $ext) {
    // Core component — never remove via this tool
    if ($ext->element === 'com_j2store') {
        $version = is_object($manifest) ? ($manifest->version ?? '?') : '?';
        return ['status' => 'core', 'reason' => 'Core component (v' . $version . ')', 'issues' => []];
    }

    $version = is_object($manifest) ? ($manifest->version ?? '?') : '?';
    $author  = is_object($manifest) ? ($manifest->author ?? '?') : '?';
    $info    = $author . ', v' . $version;

    $path = getExtensionPath($ext);

    if ($path === null || !is_dir($path)) {
        return ['status' => 'no-files', 'reason' => 'Files not found on disk (' . $info . ')', 'issues' => []];
    }

    $issues = scanForIssues($path);

    if (empty($issues)) {
        return ['status' => 'compatible', 'reason' => 'No issues found (' . $info . ')', 'issues' => []];
    }

    $joomlaIssues  = array_filter($issues, fn($i) => $i['type'] === 'joomla');
    $j2Issues      = array_filter($issues, fn($i) => $i['type'] === 'j2store');

    $parts = [];
    if (!empty($j2Issues)) {
        $parts[] = count($j2Issues) . ' J2Store API issue(s)';
    }
    if (!empty($joomlaIssues)) {
        $parts[] = count($joomlaIssues) . ' Joomla API issue(s)';
    }

    return [
        'status'  => 'incompatible',
        'reason'  => implode(', ', $parts) . ' (' . $info . ')',
        'issues'  => $issues,
    ];
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
        .j2cleanup .badge-secondary {
            background: #6c757d;
            color: #fff;
        }
        .j2cleanup details summary {
            cursor: pointer;
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
        <p style="margin-bottom: 10px;">Each extension's PHP files are scanned for deprecated Joomla and J2Store APIs:</p>
        <ul>
            <li><span class="badge badge-success">Compatible</span> — No deprecated APIs found in code</li>
            <li><span class="badge badge-danger">Incompatible</span> — Uses deprecated Joomla or J2Store APIs (details shown)</li>
            <li><span class="badge badge-secondary">No files</span> — Extension files not found on disk</li>
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
            $groups = ['incompatible' => [], 'no-files' => [], 'compatible' => [], 'core' => []];
            
            foreach ($extensions as $ext) {
                $manifest = json_decode($ext->manifest_cache);
                $result = classifyExtension($manifest, $ext);
                $ext->_status  = $result['status'];
                $ext->_reason  = $result['reason'];
                $ext->_issues  = $result['issues'];
                $ext->_manifest = $manifest;
                $groups[$result['status']][] = $ext;
            }
            ?>
            
            <?php if (!empty($groups['incompatible'])): ?>
            <h2>Incompatible Extensions (<?php echo count($groups['incompatible']); ?>)</h2>
            <p>These extensions use deprecated Joomla or J2Store APIs. They will likely not work with J2Commerce 4.x and/or Joomla 5+.</p>
            
            <table>
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" onclick="this.closest('table').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = this.checked)"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Enabled</th>
                        <th>Issues found</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups['incompatible'] as $ext): 
                        $enabled = $ext->enabled ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-warning">Disabled</span>';
                    ?>
                    <tr class="incompatible">
                        <td><input type="checkbox" name="cid[]" value="<?php echo $ext->extension_id; ?>"></td>
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><?php echo $enabled; ?></td>
                        <td>
                            <details>
                                <summary><?php echo htmlspecialchars($ext->_reason); ?></summary>
                                <ul style="margin: 5px 0; padding-left: 20px; font-size: 12px;">
                                <?php foreach ($ext->_issues as $issue): ?>
                                    <li>
                                        <span class="badge <?php echo $issue['type'] === 'j2store' ? 'badge-danger' : 'badge-warning'; ?>"><?php echo $issue['type'] === 'j2store' ? 'J2Store' : 'Joomla'; ?></span>
                                        <?php echo htmlspecialchars($issue['detail']); ?>
                                    </li>
                                <?php endforeach; ?>
                                </ul>
                            </details>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($groups['no-files'])): ?>
            <h2>No Files Found (<?php echo count($groups['no-files']); ?>)</h2>
            <p>Extension is registered in the database but files are missing from disk. Safe to remove.</p>
            
            <table>
                <thead>
                    <tr>
                        <th width="30"><input type="checkbox" onclick="this.closest('table').querySelectorAll('input[type=checkbox]').forEach(cb => cb.checked = this.checked)"></th>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($groups['no-files'] as $ext): ?>
                    <tr style="background: #2a2a2a !important; border-left: 3px solid #6c757d;">
                        <td><input type="checkbox" name="cid[]" value="<?php echo $ext->extension_id; ?>"></td>
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><?php echo htmlspecialchars($ext->_reason); ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (!empty($groups['incompatible']) || !empty($groups['no-files'])): ?>
            <input type="hidden" name="task" value="cleanup">
            <?php echo HTMLHelper::_('form.token'); ?>
            <button type="submit" class="btn" onclick="return confirm('Remove selected extensions?\n\nThis will:\n- Run uninstall scripts\n- Delete extension files\n- Remove database entries\n\nThis cannot be undone!')">
                Remove Selected Extensions
            </button>
            <?php endif; ?>
            
            <?php if (!empty($groups['compatible']) || !empty($groups['core'])): ?>
            <h2>Compatible Extensions (<?php echo count($groups['compatible']) + count($groups['core']); ?>)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Type</th>
                        <th>Element</th>
                        <th>Enabled</th>
                        <th>Details</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach (array_merge($groups['core'], $groups['compatible']) as $ext): 
                        $enabled = $ext->enabled ? '<span class="badge badge-success">Enabled</span>' : '<span class="badge badge-warning">Disabled</span>';
                        $badge = $ext->_status === 'core' ? 'badge-info' : 'badge-success';
                    ?>
                    <tr class="compatible">
                        <td><?php echo htmlspecialchars($ext->name); ?></td>
                        <td><?php echo htmlspecialchars($ext->type); ?></td>
                        <td><code><?php echo htmlspecialchars($ext->element); ?></code></td>
                        <td><?php echo $enabled; ?></td>
                        <td><span class="badge <?php echo $badge; ?>"><?php echo htmlspecialchars($ext->_reason); ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
            
            <?php if (empty($groups['incompatible']) && empty($groups['no-files'])): ?>
            <div class="alert alert-success">
                <strong>All extensions are compatible!</strong>
                <p style="margin: 10px 0 0 0;">No deprecated APIs found. Your extensions are ready for J2Commerce 4.x and Joomla 5+.</p>
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
