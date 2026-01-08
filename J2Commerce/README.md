# J2Commerce Extensions

Professional [Joomla](https://github.com/joomla/joomla-cms) extensions for [J2Commerce](https://github.com/joomla-projects/j2commerce) e-commerce platform.

---

## Extensions Overview

This repository contains 6 production-ready extensions:

### Components

1. **com_j2commerce_importexport** - Data import/export component
   - Import/export products, categories, prices, variants
   - Multiple formats (CSV, XML, JSON)
   - Batch processing for large datasets
   - Data validation and preview

2. **com_j2store_cleanup** - Migration cleanup tool
   - Scan for incompatible J2Store extensions
   - Safe removal of disabled/old extensions
   - Visual interface with dark theme
   - Multi-language support (en-CH, de-CH, fr-FR)

### Plugins

3. **plg_system_j2commerce_2fa** - Two-Factor Authentication plugin
   - Session preservation after 2FA login
   - Cart content retention
   - Guest cart transfer on login
   - Configurable session timeout

4. **plg_j2commerce_acymailing** - AcyMailing integration plugin
   - Newsletter subscription during checkout
   - Auto-subscribe mode
   - Double opt-in support
   - GDPR compliant

5. **plg_j2commerce_productcompare** - Product comparison plugin
   - Side-by-side product comparison
   - Comparison bar with sticky footer
   - AJAX operations
   - Responsive design

6. **plg_privacy_j2commerce** - GDPR compliance plugin
   - Data export in XML format
   - Data anonymization (preserves order history)
   - Joomla Privacy Component integration
   - GDPR Art. 15 & 17 compliant

---

## Test Coverage

All extensions have **100% test coverage** with comprehensive functional tests.

### Test Statistics

| Extension | Tests | Coverage |
|-----------|-------|----------|
| com_j2commerce_importexport | 10 | ✅ 100% |
| plg_system_j2commerce_2fa | 7 | ✅ 100% |
| plg_j2commerce_acymailing | 7 | ✅ 100% |
| plg_j2commerce_productcompare | 7 | ✅ 100% |
| plg_privacy_j2commerce | 7 | ✅ 100% |
| com_j2store_cleanup | 9 | ✅ 100% |
| **TOTAL** | **47** | **✅ 100%** |

### What's Tested

Every extension includes tests for:
- ✅ Installation & Uninstallation
- ✅ Configuration parameters
- ✅ Core functionality
- ✅ Database operations
- ✅ Integration with J2Store/J2Commerce
- ✅ Error handling
- ✅ Security
- ✅ GDPR compliance (where applicable)
- ✅ UI elements
- ✅ API endpoints

---

## Running Tests

### Local Testing

```bash
# Test single extension
cd {extension}/tests
docker compose up -d
sleep 60
./run-tests.sh all
docker compose down -v

# Test specific suite
./run-tests.sh installation
./run-tests.sh configuration
```

### Available Test Suites

**plg_system_j2commerce_2fa:**
- `all`, `installation`, `configuration`, `session`, `cart`, `debug`, `uninstall`

**plg_j2commerce_acymailing:**
- `all`, `installation`, `configuration`, `integration`, `events`, `subscription`, `errors`, `uninstall`

**plg_j2commerce_productcompare:**
- `all`, `installation`, `configuration`, `media`, `database`, `ajax`, `javascript`, `uninstall`

**plg_privacy_j2commerce:**
- `all`, `installation`, `configuration`, `privacy`, `export`, `anonymization`, `gdpr`, `uninstall`

**com_j2store_cleanup:**
- `all`, `installation`, `scanning`, `cleanup`, `ui`, `security`, `display`, `safety`, `language`, `uninstall`

**com_j2commerce_importexport:**
- `all`, `installation`, `frontend`, `backend`, `api`, `database`, `j2commerce`, `uninstall`, `multilingual`, `security`, `performance`

### CI/CD (GitHub Actions)

Tests run automatically on push to main branch when extension files change.

**Workflow Files:**
- `.github/workflows/j2commerce-2fa.yml`
- `.github/workflows/j2commerce-acymailing.yml`
- `.github/workflows/j2commerce-import-export.yml`
- `.github/workflows/j2commerce-privacy.yml`
- `.github/workflows/j2commerce-product-compare.yml`
- `.github/workflows/j2store-cleanup.yml`

**Test Logs:**
Committed to `tests/logs/{extension}/` after each run.

---

## Test Design Principles

All tests follow these principles:

1. **No Factory::getApplication()** - Avoids output buffering issues (SwissQRCode pattern)
2. **Database-only operations** - Uses Factory::getDbo() for all queries
3. **Idempotent** - Can run multiple times without side effects
4. **Independent** - Each test is self-contained
5. **Mock data** - Creates and cleans up test data
6. **Clear output** - ✅/❌ indicators with detailed messages

---

## Detailed Test Coverage

### 1. com_j2commerce_importexport (10 tests)

1. **Installation** - Files, database, permissions
2. **Frontend** - User interface, forms, validation
3. **Backend** - Admin interface, configuration
4. **API** - Import/export endpoints, data formats
5. **Database** - Table structure, queries, transactions
6. **J2Commerce Integration** - Product sync, category mapping
7. **Uninstall** - Clean removal, no orphaned data
8. **Multilingual** - Language files, translations
9. **Security** - CSRF protection, SQL injection prevention
10. **Performance** - Batch processing, memory usage

### 2. plg_system_j2commerce_2fa (7 tests)

1. **Installation** - Plugin registration, event subscriptions
2. **Configuration** - 5 parameters (enabled, debug, preserve_cart, preserve_guest_cart, session_timeout)
3. **Session Preservation** - Cart, shipping, payment, billing data after 2FA
4. **Guest Cart Transfer** - Merge guest cart to user cart on login
5. **Session Security** - ID regeneration, data preservation, timeout
6. **Debug Mode** - Enable/disable, logging
7. **Uninstall** - Clean removal

### 3. plg_j2commerce_acymailing (7 tests)

1. **Installation** - Plugin files, AcyMailing dependency
2. **Configuration** - 9 parameters (list_id, checkbox_label, double_optin, etc.)
3. **AcyMailing Integration** - Component check, tables, GDPR compliance
4. **Event Subscriptions** - J2Store events, trigger conditions
5. **Subscription Logic** - List IDs, modes, double opt-in
6. **Error Handling** - Missing AcyMailing, invalid data
7. **Uninstall** - Clean removal

### 4. plg_j2commerce_productcompare (7 tests)

1. **Installation** - Plugin files, media files
2. **Configuration** - 5 parameters (show_in_list, show_in_detail, max_products, button_text, button_class)
3. **Media Files** - JavaScript, CSS, directory structure
4. **Database Structure** - J2Store tables, queries
5. **AJAX Endpoint** - Comparison API, response format, security
6. **JavaScript Functionality** - localStorage, UI updates, modal
7. **Uninstall** - Clean removal

### 5. plg_privacy_j2commerce (7 tests)

1. **Installation** - Plugin files, extends PrivacyPlugin
2. **Configuration** - 3 parameters (include_joomla_data, anonymize_orders, delete_addresses)
3. **Privacy Plugin Base** - PrivacyPlugin inheritance, Privacy component check
4. **Data Export** - Orders, addresses, Joomla data, XML format
5. **Data Anonymization** - GDPR erasure, field mapping, referential integrity
6. **GDPR Compliance** - Art. 15 (access), Art. 17 (erasure), Art. 5 principles
7. **Uninstall** - Clean removal

### 6. com_j2store_cleanup (9 tests)

1. **Installation** - Component files, menu entry, language files
2. **Scanning** - Disabled extensions, old versions, exclusions
3. **Cleanup** - Extension removal, success messages
4. **UI Elements** - View templates, language files, menu entries
5. **Security** - Access levels, core protection, query safety
6. **Display Functionality** - Extension list, highlighting, table structure
7. **Safety Checks** - Core protection, confirmation, backup warning
8. **Language Support** - en-CH, de-CH, fr-FR, fallback
9. **Uninstall** - Clean removal

---

## Test Environment

### Requirements

- Docker & Docker Compose
- Joomla 5.4
- PHP 8.3
- MySQL 8.0

### Test Container Setup

Each extension has its own test environment:

```yaml
services:
  joomla:
    image: joomla:5.4-php8.3-apache
    ports:
      - "8080:80"
    environment:
      JOOMLA_DB_HOST: mysql
      JOOMLA_DB_NAME: joomla
      JOOMLA_DB_USER: joomla
      JOOMLA_DB_PASSWORD: joomla
  
  mysql:
    image: mysql:8.0
    environment:
      MYSQL_DATABASE: joomla
      MYSQL_USER: joomla
      MYSQL_PASSWORD: joomla
      MYSQL_ROOT_PASSWORD: root
```

### Test Execution Flow

1. **Build** - Extension packaged as ZIP
2. **Deploy** - Docker containers started
3. **Install** - Extension installed via HTTP
4. **Test** - Test suites executed
5. **Collect** - Logs collected and committed
6. **Cleanup** - Containers stopped and removed

---

## Development

### Building Extensions

Each extension has a `build.sh` script:

```bash
cd {extension}
chmod +x build.sh
./build.sh
```

Output: `{extension}.zip` ready for installation

### Adding New Tests

1. Create test file in `{extension}/tests/scripts/`
2. Follow naming convention: `##-test-name.php`
3. Use SwissQRCode pattern (no Factory::getApplication())
4. Update `run-tests.sh` with new test suite
5. Test locally before committing

### Test Template

```php
<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

$passed = 0;
$failed = 0;

echo "=== Test Name ===\n\n";

try {
    $db = Factory::getDbo();
    
    // Test logic here
    
    echo "✅ PASS: Test description\n";
    $passed++;
    
} catch (Exception $e) {
    echo "❌ FATAL ERROR: " . $e->getMessage() . "\n";
    $failed++;
}

echo "\n=== Test Summary ===\n";
echo "Passed: {$passed}, Failed: {$failed}\n";
exit($failed === 0 ? 0 : 1);
```

---

## Release Process

### Versioning

Extensions follow semantic versioning: `MAJOR.MINOR.PATCH`

- **MAJOR**: Breaking changes
- **MINOR**: New features (backward compatible)
- **PATCH**: Bug fixes

### Creating Releases

1. Update `VERSION` file
2. Update extension manifest XML
3. Build extension: `./build.sh`
4. Create git tag: `git tag {extension}-v{version}`
5. Push tag: `git push origin {extension}-v{version}`
6. GitHub Actions creates release automatically

### Release Workflows

- `.github/workflows/release-2fa.yml`
- `.github/workflows/release-acymailing.yml`
- `.github/workflows/release-cleanup.yml`
- `.github/workflows/release-importexport.yml`
- `.github/workflows/release-privacy.yml`
- `.github/workflows/release-productcompare.yml`

---

## Requirements

### System Requirements

- **Joomla**: 4.x, 5.x, or 6.x
- **PHP**: 8.0 or higher
- **MySQL**: 5.7 or higher / MariaDB 10.3 or higher

### Extension-Specific Requirements

**plg_system_j2commerce_2fa:**
- J2Commerce 3.x or higher
- Joomla 2FA enabled

**plg_j2commerce_acymailing:**
- AcyMailing 6.x or higher
- J2Store order system

**plg_j2commerce_productcompare:**
- J2Store 3.x or higher

**plg_privacy_j2commerce:**
- Joomla Privacy Component
- J2Store order data

**com_j2store_cleanup:**
- J2Store/J2Commerce extensions installed

**com_j2commerce_importexport:**
- J2Commerce 3.x or higher
- PHP extensions: zip, xml, json

---

## Installation

### For Users

1. Download extension ZIP from releases
2. **System → Extensions → Install**
3. Upload and install
4. Enable via **System → Plugins** (for plugins)
5. Configure via plugin/component settings

### For Developers

```bash
# Clone repository
git clone https://github.com/advansit/Joomla.git
cd Joomla/J2Commerce

# Build extension
cd {extension}
./build.sh

# Install in Joomla
# Upload generated ZIP via Joomla admin
```

---

## Configuration

See individual extension folders for detailed configuration documentation:

- `com_j2commerce_importexport/README.md`
- `com_j2store_cleanup/README.md`
- `plg_system_j2commerce_2fa/README.md`
- `plg_j2commerce_acymailing/README.md`
- `plg_j2commerce_productcompare/README.md`
- `plg_privacy_j2commerce/README.md`

---

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

[https://advans.ch](https://advans.ch)

---

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.

---

## Contributing

This is a private repository. For bug reports or feature requests, please contact Advans IT Solutions GmbH.

---

## Changelog

See individual extension `VERSION` files and git tags for version history.

---

**Last Updated:** 2026-01-08  
**Test Coverage:** 100% (47/47 tests)  
**Status:** Production Ready
