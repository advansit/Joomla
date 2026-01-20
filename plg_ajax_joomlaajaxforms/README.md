# Joomla! AJAX Forms

A Joomla plugin that provides AJAX handling for core Joomla forms, delivering a seamless user experience without page reloads.

## Features

- **Password Reset**: AJAX-powered password reset form
- **Username Reminder**: AJAX-powered username reminder form
- **JSON Responses**: Compatible with J2Commerce error format
- **Security**: CSRF protection, email enumeration prevention
- **Multilingual**: English and German translations included
- **Extensible**: Easy to add new form handlers

## Requirements

- Joomla 4.x, 5.x, or 6.x
- PHP 8.1 or higher

## Installation

1. Download the plugin package
2. Install via Joomla Extension Manager
3. Enable the plugin under System → Plugins → "Joomla! AJAX Forms"

## Configuration

### Plugin Options

| Option | Description | Default |
|--------|-------------|---------|
| Enable Password Reset | Enable AJAX for password reset form | Yes |
| Enable Username Reminder | Enable AJAX for username reminder form | Yes |

## Usage

### Template Integration

Add the following to your template overrides for `com_users/reset` and `com_users/remind`:

```php
use Joomla\CMS\Plugin\PluginHelper;

if (PluginHelper::isEnabled('ajax', 'joomlaajaxforms')) {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->registerAndUseScript('plg_ajax_joomlaajaxforms', 'plg_ajax_joomlaajaxforms/joomlaajaxforms.js', [], ['defer' => true]);
}
```

The JavaScript will automatically convert forms with the classes `.reset form.form-validate` and `.remind form.form-validate` to AJAX forms.

### JSON Response Format

Success:
```json
{
    "success": true,
    "message": "Success message",
    "data": null,
    "error": null
}
```

Error (J2Commerce compatible):
```json
{
    "success": false,
    "message": null,
    "data": null,
    "error": {
        "warning": "Error message"
    }
}
```

## Extending the Plugin

To add a new form handler, add a new case in the `onAjaxJoomlaajaxforms` method:

```php
case 'myform':
    return $this->handleMyForm();
```

## Security

- CSRF token validation on all requests
- Generic success messages to prevent email enumeration
- Input validation and sanitization

## License

Proprietary License - Copyright (C) 2025-2026 Advans IT Solutions GmbH

## Support

Advans IT Solutions GmbH  
Karl-Barth-Platz 9  
4052 Basel, Switzerland  
https://advans.ch
