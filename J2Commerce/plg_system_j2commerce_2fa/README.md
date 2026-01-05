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

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
