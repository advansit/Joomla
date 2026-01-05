# Privacy - J2Commerce Plugin

GDPR compliance made simple for your [J2Commerce](https://github.com/joomla-projects/j2commerce) store.

## Product Description

The Privacy - J2Commerce Plugin ensures your store meets GDPR requirements by integrating with [Joomla](https://github.com/joomla/joomla-cms)'s Privacy Component. Automatically handle customer data export requests with XML exports, and process deletion requests with smart anonymization options. Protects customer privacy while maintaining order history integrity for your business records.

## Features

- GDPR-compliant data export
- Data anonymization option
- Address deletion
- Joomla core data integration
- XML export format
- Automated processing

## Requirements

- [Joomla](https://github.com/joomla/joomla-cms) 4.x, 5.x or 6.x
- PHP 8.0 or higher
- J2Commerce 3.x or higher
- Joomla Privacy Component enabled

## Installation

### For Users
1. Download `plg_privacy_j2commerce.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Enable via **System → Plugins**

### For Developers
```bash
cd dev/plg_privacy_j2commerce
./build.sh
```

## Configuration

**System → Plugins → Privacy - J2Commerce**

- **Include Joomla Core Data:** Include user account and logs (Default: Yes)
- **Anonymize Orders:** Anonymize instead of delete (Default: Yes)
- **Delete Addresses:** Delete saved addresses (Default: Yes)

## Usage

### Data Export
1. User requests export via Privacy Component
2. Plugin collects J2Commerce data
3. Generates XML export
4. User receives download link

### Data Deletion
1. User requests deletion
2. Plugin processes based on config
3. Anonymizes orders (if enabled)
4. Deletes addresses (if enabled)

## Testing

```bash
cd tests/integration
cp ../../plg_privacy_j2commerce.zip test-package.zip
./run-tests.sh
```

Port: 8082

## Development

### Structure
```
plg_privacy_j2commerce/
├── README.md
├── VERSION
├── LICENSE.txt
├── j2commerce.xml
├── services/provider.php
├── src/Extension/J2Commerce.php
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
