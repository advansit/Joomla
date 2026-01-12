# J2Store Extension Cleanup Component

Safe migration tool for transitioning from J2Store to [J2Commerce](https://github.com/joomla-projects/j2commerce).

## Product Description

Until now, there was no automated way to remove old J2Store extensions that are no longer compatible with J2Commerce. The J2Store Cleanup Component solves this problem by identifying and safely removing incompatible J2Store extensions. Scan your [Joomla](https://github.com/joomla/joomla-cms) installation, review detailed extension information, and remove outdated components with confidence. Protects your valuable data while cleaning up legacy extensions that could cause conflicts.

## Features

- Scan for incompatible extensions
- List all J2Store extensions
- Safe removal process
- Backup recommendations
- Detailed extension info
- One-click cleanup

## Requirements

- [Joomla](https://github.com/joomla/joomla-cms) 4.x, 5.x or 6.x
- PHP 8.0 or higher
- Administrator access
- ⚠️ Backup recommended before use

## Installation

### For Users
1. Download `com_j2store_cleanup.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Access via **Components → J2Store Cleanup**

### For Developers
```bash
cd dev/com_j2store_cleanup
./build.sh
```

## Usage

### Scanning
1. **Components → J2Store Cleanup**
2. Automatic scan on load
3. Review found extensions
4. Check compatibility status

### Removing
1. Select extensions to remove
2. Click **Remove**
3. Confirm removal
4. Extensions uninstalled

⚠️ **Always backup before removing extensions**

## What Gets Removed

- J2Store plugins
- J2Store modules
- J2Store components
- J2Store libraries
- Associated database tables (optional)

## What Stays

- J2Commerce extensions
- Product data
- Order history
- Customer information

## Automated Testing

This component has comprehensive automated tests that run on every push via GitHub Actions.

### Test Suites

1. **Installation** - Component registration, file verification
2. **Scanning** - J2Store extension detection, mock extension creation
3. **Cleanup** - Extension removal, J2Commerce preservation
4. **Uninstall** - Component removal, verification

### Running Tests Locally

```bash
cd tests
docker compose up -d
sleep 120  # Wait for Joomla initialization
./run-tests.sh all
docker compose down -v
```

Test results are saved in `tests/test-results/` and committed to `tests/logs/`.

## Development

### Structure
```
com_j2store_cleanup/
├── README.md
├── VERSION
├── LICENSE.txt
├── j2store_cleanup.xml
├── administrator/
│   ├── j2store_cleanup.php
│   └── language/ (en-CH, de-CH, fr-FR)
└── tests/
```

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
