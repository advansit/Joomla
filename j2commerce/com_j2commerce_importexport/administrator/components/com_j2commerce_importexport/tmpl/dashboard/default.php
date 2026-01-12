<?php
/**
 * @package     J2Commerce Import/Export Component
 * @subpackage  Administrator
 * @copyright   Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
 * @license     Proprietary
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>

<div class="j2commerce-importexport">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_EXPORT'); ?></h3>
                </div>
                <div class="card-body">
                    <form action="<?php echo Route::_('index.php?option=com_j2commerce_importexport&task=export.export'); ?>" method="post" id="export-form">
                        <div class="form-group">
                            <label for="export-type"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_TYPE'); ?></label>
                            <select name="type" id="export-type" class="form-select">
                                <option value="products"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_PRODUCTS'); ?></option>
                                <option value="categories"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_CATEGORIES'); ?></option>
                                <option value="variants"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_VARIANTS'); ?></option>
                                <option value="prices"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_PRICES'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="export-format"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_FORMAT'); ?></label>
                            <select name="format" id="export-format" class="form-select">
                                <option value="csv">CSV</option>
                                <option value="xml">XML</option>
                                <option value="json">JSON</option>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary">
                            <?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_EXPORT_BUTTON'); ?>
                        </button>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h3><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_IMPORT'); ?></h3>
                </div>
                <div class="card-body">
                    <form action="<?php echo Route::_('index.php?option=com_j2commerce_importexport&task=import.upload'); ?>" method="post" enctype="multipart/form-data" id="import-form">
                        <div class="form-group">
                            <label for="import-type"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_TYPE'); ?></label>
                            <select name="type" id="import-type" class="form-select">
                                <option value="products"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_PRODUCTS'); ?></option>
                                <option value="variants"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_VARIANTS'); ?></option>
                                <option value="prices"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_PRICES'); ?></option>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="import-file"><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_FILE'); ?></label>
                            <input type="file" name="import_file" id="import-file" class="form-control" accept=".csv,.xml,.json" required>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_UPLOAD_BUTTON'); ?>
                        </button>
                    </form>
                    
                    <div id="import-preview" class="mt-3" style="display:none;">
                        <h4><?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_PREVIEW'); ?></h4>
                        <div id="preview-content"></div>
                        <button type="button" class="btn btn-primary" id="process-import">
                            <?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_PROCESS_BUTTON'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const importForm = document.getElementById('import-form');
    const importPreview = document.getElementById('import-preview');
    const previewContent = document.getElementById('preview-content');
    const processButton = document.getElementById('process-import');
    
    importForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Show preview
                importPreview.style.display = 'block';
                loadPreview();
            } else {
                alert(data.error || 'Upload failed');
            }
        })
        .catch(error => {
            alert('Error: ' + error.message);
        });
    });
    
    function loadPreview() {
        fetch('index.php?option=com_j2commerce_importexport&task=import.preview&format=json')
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                displayPreview(data.data);
            }
        });
    }
    
    function displayPreview(data) {
        let html = '<table class="table table-striped"><thead><tr>';
        data.headers.forEach(header => {
            html += '<th>' + header + '</th>';
        });
        html += '</tr></thead><tbody>';
        
        data.rows.forEach(row => {
            html += '<tr>';
            Object.values(row).forEach(value => {
                html += '<td>' + value + '</td>';
            });
            html += '</tr>';
        });
        
        html += '</tbody></table>';
        html += '<p>Total rows: ' + data.total + '</p>';
        
        previewContent.innerHTML = html;
    }
    
    processButton.addEventListener('click', function() {
        if (!confirm('<?php echo Text::_('COM_J2COMMERCE_IMPORTEXPORT_CONFIRM_IMPORT'); ?>')) {
            return;
        }
        
        const type = document.getElementById('import-type').value;
        
        fetch('index.php?option=com_j2commerce_importexport&task=import.process&format=json', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                type: type,
                mapping: {} // Auto-mapping for now
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Imported: ' + data.imported + ', Failed: ' + data.failed);
                location.reload();
            } else {
                alert(data.error || 'Import failed');
            }
        });
    });
});
</script>
