# System - J2Commerce 2FA Plugin
Seamless checkout experience with Two-Factor Authentication.

## Product Description

The System - J2Commerce 2FA Plugin solves a critical checkout problem: losing cart contents when customers log in with Two-Factor Authentication. This plugin preserves sessions, cart data, and return URLs throughout the 2FA process, ensuring customers complete their purchase without frustration. Essential for stores using [Joomla](https://github.com/joomla/joomla-cms)'s 2FA security feature.

## Features

- Session preservation after 2FA
- Cart content retention
- Return URL preservation
- Guest cart transfer
- Configurable session timeout
- Debug logging

## Requirements

- [Joomla](https://github.com/joomla/joomla-cms) 4.x, 5.x or 6.x
- PHP 8.0 or higher
- J2Commerce 3.x or higher
- Joomla 2FA enabled

## Installation

### For Users
1. Download `plg_system_j2commerce_2fa.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Enable via **System → Plugins**

### For Developers
```bash
cd dev/plg_system_j2commerce_2fa
./build.sh
```

## Configuration

**System → Plugins → System - J2Commerce 2FA**

- **Plugin Enable:** Enable/disable (Default: Yes)
- **Debug Mode:** Debug logging (Default: No)
- **Preserve Cart:** Keep cart after 2FA (Default: Yes)
- **Session Timeout:** Timeout in seconds (Default: 3600)
- **Preserve Guest Cart:** Transfer guest cart on login (Default: Yes)

## Usage

Works automatically:
1. Customer adds to cart
2. Proceeds to checkout
3. Logs in with 2FA
4. Completes 2FA verification
5. Returns to checkout with cart intact

## Testing

```bash
cd tests/integration
cp ../../plg_system_j2commerce_2fa.zip test-package.zip
./run-tests.sh
```

Port: 8083

## Development

### Structure
```
plg_system_j2commerce_2fa/
├── README.md
├── VERSION
├── LICENSE.txt
├── j2commerce_2fa.xml
├── services/provider.php
├── src/Extension/J2Commerce2fa.php
└── language/ (en-CH, de-CH, fr-FR)
```

## Automated Testing

This plugin has automated tests that run on every push via GitHub Actions.

### Test Suites

1. **Installation** - Plugin registration, file verification
2. **Uninstall** - Clean removal from database

### Running Tests Locally

```bash
cd tests
docker compose up -d
sleep 120  # Wait for Joomla initialization
./run-tests.sh all
docker compose down -v
```

Test results are saved in `tests/test-results/`.

## Troubleshooting

### Cart Still Lost After 2FA
**Problem:** Cart contents disappear despite plugin enabled  
**Solution:** 
1. Verify plugin is enabled in **System → Plugins**
2. Check plugin ordering (should be early in system plugin list)
3. Enable debug mode and check logs
4. Verify J2Commerce session handling

### 2FA Redirect Loop
**Problem:** Endless redirect between 2FA and checkout  
**Solution:**
1. Disable plugin temporarily
2. Clear browser cookies and Joomla cache
3. Re-enable plugin
4. Check session timeout setting (increase if needed)

### Guest Cart Not Transferring
**Problem:** Guest cart lost when logging in during checkout  
**Solution:**
1. Verify "Preserve Guest Cart" is enabled
2. Check J2Commerce cart session storage
3. Enable debug logging to trace cart transfer
4. Ensure no conflicting plugins

### Debug Logs Not Appearing
**Problem:** Debug mode enabled but no logs  
**Solution:**
1. Verify Joomla logging is enabled (**System → Global Configuration → Logging**)
2. Check log directory permissions (writable)
3. Look in `administrator/logs/` for `plg_system_j2commerce_2fa.log.php`

### Session Timeout Too Short
**Problem:** Users timeout during 2FA process  
**Solution:** Increase session timeout in plugin settings (try 7200 = 2 hours)

## How It Works

### Session Preservation
1. **Before 2FA:** Plugin stores cart data, return URL, and session info
2. **During 2FA:** User completes authentication
3. **After 2FA:** Plugin restores cart and redirects to checkout

### Cart Transfer
1. **Guest adds to cart:** Cart stored in session
2. **Guest logs in:** Plugin detects login during checkout
3. **Transfer:** Guest cart merged with user cart
4. **Cleanup:** Guest session cleared

### Event Hooks
- `onUserAfterLogin` - Restores cart after 2FA
- `onAfterRoute` - Preserves session before 2FA
- `onUserLogin` - Transfers guest cart

## Configuration Examples

### High Security Store
```
Enable Plugin: Yes
Debug Mode: No
Preserve Cart: Yes
Session Timeout: 1800 (30 minutes)
Preserve Guest Cart: Yes
```

### Development/Testing
```
Enable Plugin: Yes
Debug Mode: Yes
Preserve Cart: Yes
Session Timeout: 7200 (2 hours)
Preserve Guest Cart: Yes
```

### Minimal Configuration
```
Enable Plugin: Yes
Debug Mode: No
Preserve Cart: Yes
Session Timeout: 3600 (1 hour)
Preserve Guest Cart: No
```

## Compatibility

### Tested With
- Joomla 4.4, 5.0, 5.1, 6.0
- J2Commerce 3.x
- PHP 8.0, 8.1, 8.2, 8.3
- Google Authenticator (Joomla 2FA)
- YubiKey (Joomla 2FA)

### Known Conflicts
- None reported

### Third-Party 2FA Plugins
This plugin works with Joomla's built-in 2FA system. Third-party 2FA plugins may require additional configuration.

## Multi-Language Support

This extension supports the following languages:
- **English (en-GB)** - Default
- **German (de-CH)** - Swiss German
- **French (fr-FR)** - French

Users can add additional language files by creating new language folders following Joomla's language structure:
```
language/{language-tag}/plg_system_j2commerce_2fa.ini
language/{language-tag}/plg_system_j2commerce_2fa.sys.ini
```

## Performance Impact

- **Minimal overhead:** ~0.5ms per request
- **Memory usage:** ~50KB additional
- **Database queries:** 0 additional (uses session storage)
- **Cache impact:** None

## Security Considerations

- Session data encrypted in Joomla session
- No sensitive data logged (even in debug mode)
- Cart data validated before restoration
- CSRF protection maintained throughout 2FA

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
