# J2Store Extension Cleanup Component
Safe migration tool for transitioning from J2Store to [J2Commerce](https://github.com/joomla-projects/j2commerce).

## Description

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
1. Download `com_j2store_cleanup.zip`
2. **System → Extensions → Install**
3. Upload and install
4. Access via **Components → J2Store Cleanup**
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

This component has automated tests that run on every push via GitHub Actions.

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

## Troubleshooting

### No J2Store Extensions Found
**Problem:** Scan shows no extensions but you know they exist  
**Solution:** Check if extensions are already uninstalled. Verify in **System → Extensions → Manage**.

### Removal Fails with Database Error
**Problem:** Cannot remove extension due to database constraints  
**Solution:** Manually disable extension first in **System → Plugins** or **System → Modules**, then retry removal.

### J2Commerce Extensions Detected as J2Store
**Problem:** Component incorrectly identifies J2Commerce extensions  
**Solution:** This should not happen. Report as bug with extension details.

### Cannot Access Component After Installation
**Problem:** Menu item missing or permission denied  
**Solution:** Verify administrator permissions. Check **System → Global Configuration → Permissions**.

### Backup Recommendations Ignored
**Problem:** Removed extensions without backup  
**Solution:** If data loss occurs, restore from Joomla backup or database snapshot. Always backup before cleanup.

## Safety Features

### Pre-Removal Checks
- Verifies extension is J2Store (not J2Commerce)
- Checks for active dependencies
- Warns about data loss
- Requires explicit confirmation

### Protected Extensions
The component will **never** remove:
- J2Commerce core components
- J2Commerce plugins
- J2Commerce modules
- Active Joomla core extensions

### Rollback Options
- Database backup recommended before cleanup
- Akeeba Backup integration (if installed)
- Manual restoration from backup

## Migration Workflow

### Step 1: Preparation
1. **Backup entire site** (files + database)
2. Install J2Commerce
3. Migrate data from J2Store to J2Commerce
4. Test J2Commerce functionality

### Step 2: Verification
1. Verify all products migrated
2. Check order history
3. Test checkout process
4. Confirm customer accounts

### Step 3: Cleanup
1. Install J2Store Cleanup Component
2. Run scan
3. Review detected extensions
4. Remove J2Store extensions
5. Verify site functionality

### Step 4: Post-Cleanup
1. Clear Joomla cache
2. Test all store functions
3. Monitor for errors
4. Remove cleanup component (optional)

## Extension Detection

### Detected Patterns
The component identifies J2Store extensions by:
- Extension name prefix: `j2store_*`, `plg_j2store_*`, `mod_j2store_*`
- Component element: `com_j2store`
- Plugin group: `j2store`
- Module prefix: `mod_j2store`

### False Positives
If legitimate extensions are detected:
1. Do not remove them
2. Report to support with extension details
3. Manually exclude from removal list

## Multi-Language Support

This extension supports the following languages:
- **English (en-GB)** - Default
- **German (de-CH)** - Swiss German
- **French (fr-FR)** - French

Users can add additional language files by creating new language folders following Joomla's language structure:
```
administrator/language/{language-tag}/com_j2store_cleanup.ini
administrator/language/{language-tag}/com_j2store_cleanup.sys.ini
```

## Known Limitations

- Cannot restore removed extensions automatically
- Requires manual backup before use
- Does not migrate data (use separate migration tool)
- Cannot detect custom J2Store modifications

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
