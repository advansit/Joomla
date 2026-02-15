# Joomla! AJAX Forms

[![Build & Test](https://github.com/advansit/Joomla/actions/workflows/joomla-ajax-forms.yml/badge.svg)](https://github.com/advansit/Joomla/actions/workflows/joomla-ajax-forms.yml)
[![Release](https://github.com/advansit/Joomla/actions/workflows/release-joomla-ajax-forms.yml/badge.svg)](https://github.com/advansit/Joomla/actions/workflows/release-joomla-ajax-forms.yml)
[![Joomla 5](https://img.shields.io/badge/Joomla-5.x-blue.svg)](https://www.joomla.org/)
[![Joomla 6](https://img.shields.io/badge/Joomla-6.x-blue.svg)](https://www.joomla.org/)
[![Joomla 7](https://img.shields.io/badge/Joomla-7.x-blue.svg)](https://www.joomla.org/)
[![PHP 8.1+](https://img.shields.io/badge/PHP-8.1%2B-purple.svg)](https://www.php.net/)

A Joomla plugin that provides AJAX handling for user forms, authentication, profile management, and J2Store/J2Commerce cart operations — without page reloads.

## Features

| Feature | AJAX Task | Description |
|---------|-----------|-------------|
| Login | `login` | Authentication with MFA/2FA support |
| Logout | `logout` | Session termination with redirect |
| Registration | `register` | User registration with email verification and admin approval |
| Password Reset | `reset` | Password reset email request |
| Username Reminder | `remind` | Username reminder email request |
| MFA Validation | `mfa_validate` | Multi-factor authentication code verification |
| Profile Editing | `saveProfile` | Update name, email, password |
| Cart: Remove Item | `removeCartItem` | Remove item from J2Store/J2Commerce cart |
| Cart: Get Count | `getCartCount` | Get current cart item count |

All features can be individually enabled/disabled via plugin parameters.

## Requirements

- Joomla 5.x, 6.x, or 7.x
- PHP 8.1+
- J2Store or J2Commerce (only for cart features)

## Installation

1. Download `plg_ajax_joomlaajaxforms.zip` from the [latest release](https://github.com/advansit/Joomla/releases?q=ajaxforms)
2. Install via Joomla Extension Manager
3. Enable under System > Plugins > "Joomla! AJAX Forms"

The installer checks the `.htaccess` on the web server. If rewrite rules block `/component/` or `index.php?option=com_*` URLs, `com_ajax` must be whitelisted — otherwise all AJAX calls will fail silently. The installer warns if exceptions are missing.

Required `.htaccess` exceptions (only if URL blocking is active):

```apache
# Allow com_ajax plugin calls through /component/ blocking
RewriteCond %{QUERY_STRING} !plugin= [NC]

# Allow com_ajax through index.php?option= blocking
RewriteCond %{QUERY_STRING} !^option=com_ajax [NC]
```

## Configuration

| Parameter | Description | Default |
|-----------|-------------|---------|
| Enable Login | AJAX login with MFA support | Yes |
| Enable Registration | AJAX user registration | Yes |
| Enable Password Reset | AJAX password reset | Yes |
| Enable Username Reminder | AJAX username reminder | Yes |
| Enable Profile Editing | AJAX profile save (name, email, password) | Yes |
| Enable J2Store Cart | AJAX cart operations (requires J2Store/J2Commerce) | Yes |

## Usage

### Template Integration

Load the script in your template overrides:

```php
use Joomla\CMS\Plugin\PluginHelper;

if (PluginHelper::isEnabled('ajax', 'joomlaajaxforms')) {
    $wa = Factory::getApplication()->getDocument()->getWebAssetManager();
    $wa->registerAndUseScript('plg_ajax_joomlaajaxforms', 'plg_ajax_joomlaajaxforms/joomlaajaxforms.js', [], ['defer' => true]);
}
```

The plugin automatically initializes form handlers for login, reset, remind, and registration forms. For cart and profile operations, use the JavaScript API:

```javascript
// Remove cart item
JoomlaAjaxForms.removeCartItem(cartItemId, clickedElement, callback);

// Save profile form
JoomlaAjaxForms.saveProfile(formElement, callback);

// Logout
JoomlaAjaxForms.logout(returnUrl);
```

### JSON Response Format

```json
{
    "success": true,
    "message": "Success message",
    "data": { },
    "error": null
}
```

Error responses use J2Commerce-compatible format:

```json
{
    "success": false,
    "message": null,
    "data": null,
    "error": { "warning": "Error message" }
}
```

## Joomla Compatibility

The plugin avoids all APIs deprecated in Joomla 6:

- Uses `$this->getApplication()` instead of `Factory::getApplication()`
- Uses `MailerFactoryInterface` instead of `Factory::getMailer()`
- Uses `UserFactoryInterface` instead of `User::getInstance()`
- Uses `->getInput()` instead of `->input`

## Tests

11 test scripts covering installation, configuration, endpoint access, login, registration, reset, remind, security, profile, J2Store cart, and uninstall. Run via Docker:

```bash
cd plg_ajax_joomlaajaxforms/tests
docker compose up --build --abort-on-container-exit
```

## Translations

- English (en-GB)
- German (de-DE) — Swiss Standard German (no ß)

82 language keys covering all UI labels, error messages, email templates, and JavaScript strings.

## License

Proprietary License — Copyright (C) 2025-2026 Advans IT Solutions GmbH

See [LICENSE.txt](../LICENSE.txt) for details.
