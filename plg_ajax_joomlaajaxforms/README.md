# Joomla! AJAX Forms

[![Build & Test](https://github.com/advansit/Joomla/actions/workflows/joomla-ajax-forms.yml/badge.svg)](https://github.com/advansit/Joomla/actions/workflows/joomla-ajax-forms.yml)
[![Joomla 4](https://img.shields.io/badge/Joomla-4.x-blue.svg)](https://www.joomla.org/)
[![Joomla 5](https://img.shields.io/badge/Joomla-5.x-blue.svg)](https://www.joomla.org/)
[![Joomla 6](https://img.shields.io/badge/Joomla-6.x-blue.svg)](https://www.joomla.org/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://www.php.net/)

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

## Changelog

### v1.0.0 (2026-01)
- Initial release
- Password reset via AJAX
- Username reminder via AJAX
- English and German translations

## License

Proprietary License - Copyright (C) 2025-2026 Advans IT Solutions GmbH

See [LICENSE.txt](../LICENSE.txt) for details.

## Support

Advans IT Solutions GmbH  
Karl-Barth-Platz 9  
4052 Basel, Switzerland  
https://advans.ch
