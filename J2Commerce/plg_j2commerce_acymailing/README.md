# J2Commerce AcyMailing Integration Plugin

Seamless newsletter subscription integration for your [J2Commerce](https://github.com/joomla-projects/j2commerce) store.

## Product Description

The J2Commerce AcyMailing Plugin connects your e-commerce checkout with AcyMailing newsletter management. Grow your subscriber base by offering newsletter opt-in during checkout, on product pages, or automatically. Supports multiple mailing lists, double opt-in for GDPR compliance, and guest subscriptions. Turn every customer into a potential newsletter subscriber with minimal friction.

## Features

- Checkout integration with subscription checkbox
- Product page subscription option
- Automatic subscription (without user interaction)
- Guest user subscription support
- Multiple mailing list subscription
- Double opt-in support
- Customizable subscription prompts

## Requirements

- [Joomla](https://github.com/joomla/joomla-cms) 4.x, 5.x or 6.x
- PHP 8.0 or higher
- J2Commerce 3.x or higher
- AcyMailing component installed

## Installation

### For Users

1. Download `plg_j2commerce_acymailing.zip`
2. Go to **System → Extensions → Install**
3. Upload and install the package
4. Enable via **System → Plugins**
5. Configure settings

### For Developers

```bash
# Clone repository
git clone https://github.com/advansit/advans.ch.git
cd advans.ch/dev/plg_j2commerce_acymailing

# Build package
./build.sh

# Package will be created as plg_j2commerce_acymailing.zip
```

## Configuration

Access via **System → Plugins → J2Commerce - AcyMailing**.

### Settings

**List ID** (Required)
- AcyMailing list identifier
- Get from AcyMailing → Lists

**Checkbox Label**
- Text displayed next to checkbox
- Default: "Subscribe to newsletter"
- Supports language constants

**Checkbox Default State**
- Checked or unchecked by default
- Default: Unchecked (GDPR compliant)

**Double Opt-in**
- Require email confirmation
- Default: Enabled
- Recommended for compliance

**Show in Checkout**
- Display during checkout
- Default: Enabled

**Auto Subscribe**
- Subscribe without checkbox
- Default: Disabled
- ⚠️ Ensure legal compliance

**Show in Product Pages**
- Display on product detail pages
- Default: Disabled

**Allow Guest Subscription**
- Enable for non-registered users
- Default: Enabled

**Multiple Lists**
- Comma-separated list IDs (e.g., 1,2,3)
- Leave empty for single list

## Usage

### Basic Setup

1. Install and enable plugin
2. Configure List ID
3. Enable "Show in Checkout"
4. Test with a checkout

### Auto-Subscribe Mode

1. Enable "Auto Subscribe"
2. Disable "Show in Checkout"
3. All customers automatically subscribed
4. ⚠️ Verify legal compliance

## Testing

### Integration Tests

Docker-based integration tests are available:

```bash
cd tests/integration

# Build extension first
cd ../..
./build.sh

# Copy package to test directory
cp plg_j2commerce_acymailing.zip tests/integration/test-package.zip

# Run tests
cd tests/integration
./run-tests.sh
```

**Test Categories:**
- Installation verification
- Activation check
- Configuration validation
- Functionality tests

**Requirements:**
- Docker
- Docker Compose

**Ports:** Tests run on port 8080

### Manual Testing

1. Install plugin
2. Configure List ID
3. Add product to cart
4. Go to checkout
5. Verify checkbox appears
6. Complete order
7. Check AcyMailing for new subscriber

## Development

### File Structure

```
plg_j2commerce_acymailing/
├── README.md                    # This file
├── VERSION                      # Version number
├── LICENSE.txt                  # License
├── acymailing.xml              # Manifest
├── build.sh                    # Build script
├── script.php                  # Installation script
├── services/
│   └── provider.php            # Service provider
├── src/
│   └── Extension/
│       └── AcyMailing.php      # Main plugin class
├── language/
│   ├── en-CH/                  # English (Swiss)
│   ├── de-CH/                  # German (Swiss)
│   └── fr-FR/                  # French
├── tests/
│   ├── unit/                   # PHPUnit tests
│   └── integration/            # Docker tests
└── tmpl/                       # Templates
```

### Building

```bash
# Build package
./build.sh

# Update version
../update-version.sh plg_j2commerce_acymailing 1.0.1

# Rebuild
./build.sh
```

### Code Standards

- PSR-4 autoloading
- PSR-12 coding standards
- Joomla coding standards
- PHP 8.0+ type hints
- Namespaced classes

## Troubleshooting

### Checkbox not showing

**Check:**
- Plugin is enabled
- "Show in Checkout" is enabled
- Template compatibility
- JavaScript console for errors

### Subscriptions not working

**Check:**
- AcyMailing is installed
- List ID is correct
- Email is valid
- Check Joomla error logs

### Double opt-in emails not sent

**Check:**
- [Joomla](https://github.com/joomla/joomla-cms) email configuration
- AcyMailing queue status
- Spam folders
- Email server logs

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
