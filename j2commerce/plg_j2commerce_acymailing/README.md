# J2Commerce AcyMailing Integration Plugin

![Pre-Release](https://img.shields.io/badge/status-pre--release-orange)

Seamless newsletter subscription integration for your [J2Commerce](https://github.com/joomla-projects/j2commerce) store.

⚠️ **Pre-Release Notice:** This extension is currently in pre-release status. While fully functional and tested, it has not yet been deployed in production environments. Use in production at your own discretion.

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

## Advanced Configuration

### Multiple Mailing Lists
Subscribe users to multiple lists simultaneously:
```
List ID: 1,2,3
```
Users will be subscribed to lists 1, 2, and 3.

### Custom Checkbox Text
Use language constants for multi-language support:
```
Checkbox Label: PLG_J2COMMERCE_ACYMAILING_SUBSCRIBE_TEXT
```

Define in language files:
```ini
PLG_J2COMMERCE_ACYMAILING_SUBSCRIBE_TEXT="Subscribe to our newsletter"
```

### Conditional Subscription
Subscribe based on product category or user group (requires custom code):
```php
// In your template override
if ($product->category_id == 5) {
    // Show subscription checkbox
}
```

## GDPR Compliance

### Best Practices
1. **Default unchecked:** Checkbox should be unchecked by default
2. **Clear consent:** Label clearly states what user subscribes to
3. **Double opt-in:** Enable email confirmation
4. **Privacy policy:** Link to privacy policy near checkbox
5. **Easy unsubscribe:** AcyMailing provides unsubscribe links

### Recommended Settings
```
Checkbox Default State: Unchecked
Double Opt-in: Enabled
Auto Subscribe: Disabled
```

### Legal Considerations
- **EU/Switzerland:** Requires explicit consent (opt-in)
- **Canada (CASL):** Requires express consent
- **USA (CAN-SPAM):** Allows opt-out
- **Australia (Spam Act):** Requires consent

Consult legal counsel for your jurisdiction.

## Integration Examples

### Checkout Integration
```php
// Checkbox appears in checkout form
// User checks box
// Order completed
// User subscribed to AcyMailing
```

### Product Page Integration
```php
// Enable "Show in Product Pages"
// Checkbox appears on product detail
// User subscribes before adding to cart
```

### Auto-Subscribe (Use with caution)
```php
// Enable "Auto Subscribe"
// Every customer automatically subscribed
// No checkbox shown
// ⚠️ Ensure legal compliance
```

## Customization

### Styling the Checkbox
```css
/* Custom checkbox styling */
.j2commerce-acymailing-checkbox {
    display: flex;
    align-items: center;
    margin: 15px 0;
}

.j2commerce-acymailing-checkbox input[type="checkbox"] {
    margin-right: 10px;
    width: 20px;
    height: 20px;
}

.j2commerce-acymailing-checkbox label {
    font-size: 14px;
    cursor: pointer;
}
```

### Template Override
Create template override in your template:
```
templates/your-template/html/plg_j2commerce_acymailing/
└── default.php
```

### Custom Subscription Logic
```php
// plugins/j2commerce/acymailing/src/Extension/AcyMailing.php
public function onJ2CommerceAfterCheckout($order) {
    // Custom logic here
    if ($order->total > 100) {
        // Subscribe high-value customers to VIP list
        $this->subscribeToList($order->email, 5);
    }
}
```

## Performance Considerations

- **AJAX subscription:** Non-blocking, doesn't slow checkout
- **Batch processing:** Multiple lists subscribed in single call
- **Caching:** Plugin settings cached for performance
- **Database queries:** Minimal impact (1-2 queries per checkout)

## Multi-Language Support

This extension supports the following languages:
- **English (en-GB)** - Default
- **German (de-CH)** - Swiss German
- **French (fr-FR)** - French

Users can add additional language files by creating new language folders following Joomla's language structure:
```
language/{language-tag}/plg_j2commerce_acymailing.ini
language/{language-tag}/plg_j2commerce_acymailing.sys.ini
```

## Compatibility

### Tested With
- Joomla 4.4, 5.0, 5.1, 6.0
- J2Commerce 3.x
- AcyMailing 7.x, 8.x, 9.x
- PHP 8.0, 8.1, 8.2, 8.3

### Known Conflicts
- None reported

### Third-Party Extensions
Works with most Joomla extensions. Report conflicts to support.

## Troubleshooting Extended

### Subscribers Not Appearing in AcyMailing
**Problem:** Subscription completes but user not in list  
**Solution:**
1. Check AcyMailing queue (**AcyMailing → Configuration → Queue**)
2. Verify list ID is correct
3. Check if user already subscribed
4. Look for duplicate email addresses
5. Check AcyMailing logs

### Checkbox Appears Multiple Times
**Problem:** Duplicate checkboxes in checkout  
**Solution:**
1. Check for multiple plugin instances
2. Verify template doesn't duplicate plugin position
3. Disable conflicting plugins
4. Clear Joomla cache

### Guest Subscriptions Not Working
**Problem:** Guest users cannot subscribe  
**Solution:**
1. Enable "Allow Guest Subscription"
2. Verify email field is filled
3. Check AcyMailing guest user settings
4. Test with registered user first

### Double Opt-in Emails in Spam
**Problem:** Confirmation emails go to spam  
**Solution:**
1. Configure SPF/DKIM records
2. Use authenticated SMTP
3. Check email content for spam triggers
4. Test with different email providers
5. Whitelist sender in AcyMailing

### Auto-Subscribe Legal Issues
**Problem:** Concerned about GDPR compliance  
**Solution:**
1. Disable auto-subscribe
2. Use opt-in checkbox instead
3. Add privacy policy link
4. Enable double opt-in
5. Consult legal counsel

## Support & Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

https://advans.ch

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.
