# System - J2Commerce Privacy Plugin
**Author:** Advans IT Solutions GmbH  
**License:** Proprietary  
**Joomla:** 4.x, 5.x, 6.x  
**PHP:** 8.0+

GDPR/DSGVO compliance solution for J2Commerce. Features:
- **Checkout Consent Checkbox** - Privacy consent during checkout
- **Privacy Policy Link** - Configurable link to privacy policy article
- **Address Management** - Frontend delete buttons for saved addresses
- **Automated Data Cleanup** - Scheduled anonymization after retention period

---

## Table of Contents

### Quick Start
1. [Installation](#installation)
2. [Quick Setup Guide](#quick-setup-guide)
3. [Configuration](#configuration)

### Core Features
4. [Features Overview](#features-overview)
5. [How It Works](#how-it-works)
6. [Lifetime License Detection](#lifetime-license-detection)

### User Guides
7. [Usage Guide](#usage-guide)
8. [Administrator Guide](#administrator-guide)
9. [Workflow Examples](#workflow-examples)

### Configuration
10. [Legal Basis Examples](#legal-basis-examples)
11. [Multi-Language Support](#multi-language-support)
12. [J2Store Custom Fields](#j2store-custom-fields)

### Technical
13. [Testing](#testing)
14. [Development](#development)
15. [Support](#support)

---

## Installation

### Requirements

- Joomla 4.x, 5.x or 6.x
- PHP 8.0 or higher
- J2Store 3.3.0 or higher
- Joomla Privacy Component enabled

### Scope and Limitations

**Supported Product Types:**
- One-time purchases with standard retention periods
- Perpetual software licenses with extended data retention

**Current Limitations:**

This version does not include automated handling of recurring subscription products. Subscription-based products require:
- Real-time payment gateway integration for subscription status verification
- Complex state management for active, paused, and cancelled subscriptions
- Renewal cycle tracking and grace period handling

Organizations with subscription-based business models should contact Advans IT Solutions for custom implementation requirements.

**Recommended Approach for Subscriptions:**
- Apply standard accounting retention periods to all subscription orders
- Implement manual review processes for active subscription accounts
- Maintain separate documentation of subscription-specific data retention policies

### Install Steps

1. Download `plg_system_j2commerce_privacy.zip`
2. **System → Extensions → Install**
3. Upload and install
4. **Enable via System → Plugins → System - J2Commerce Privacy**

### Post-Installation Configuration

The plugin requires mandatory configuration before operation. A detailed setup wizard is displayed upon installation. The following steps must be completed:

1. Create J2Store Custom Field for license type identification
2. Configure product-level license classifications
3. Enable the plugin in Joomla's plugin manager
4. Configure retention periods and legal compliance parameters
5. Establish automated cleanup scheduling

Note: Failure to complete the Custom Field configuration will result in incorrect license type detection and potential data retention violations.

---

## Implementation Guide

### Estimated Implementation Time: 20-30 minutes

### Step 1: Configure J2Store Custom Field (Required)

**Navigate to:**
```
Components → J2Store → Setup → Custom Fields → New
```

**Configure EXACTLY as follows:**

| Setting | Value |
|---------|-------|
| **Field Name** | `is_lifetime_license` |
| **Field Label** | Lifetime License |
| **Field Type** | Radio |
| **Display in** | Product |
| **Required** | No |
| **Published** | Yes |
| **Options** | Yes / No |
| **Default Value** | No |

Click **Save & Close** to persist the configuration.

---

### Step 2: Product Classification

For each product requiring perpetual license handling:

1. Navigate to: `Components → J2Store → Catalog → Products`
2. Open the product
3. Scroll to **Custom Fields** section
4. Set **Lifetime License: Yes**
5. **Save & Close**

Repeat this process for all products requiring perpetual license data retention.

---

### Step 3: Plugin Activation

1. Navigate to: `System → Plugins`
2. Locate: `Privacy - J2Commerce`
3. Change status from Disabled to Enabled

---

### Step 4: Configure Retention Parameters

Navigate to: `System → Plugins → Privacy - J2Commerce`

#### Data Handling Configuration

- **Include Joomla Core Data:** Enable to include Joomla user account data in privacy exports
- **Anonymize Orders:** Enable to anonymize rather than delete order records (recommended for accounting compliance)
- **Delete Addresses:** Enable to remove stored address data upon data removal requests

#### Retention Settings

**Retention Period (Years):**
- Switzerland: `10`
- Germany: `10`
- Austria: `7`
- France: `10`
- Spain: `6`
- UK: `6`
- USA: `7`

**Legal Basis:** (Example for Switzerland)
```
• Switzerland: OR Art. 958f (10 years)
```

See [Legal Basis Examples](#legal-basis-examples) for more countries.

**Support Email:**
```
privacy@example.com
```

⚠️ **WICHTIG:** Ändern Sie diese Email-Adresse zu Ihrer tatsächlichen Privacy-Kontakt-Email. Diese Adresse wird in allen Retention-Nachrichten an Benutzer angezeigt.

**Wo ändern:**
- `System → Plugins → Privacy - J2Commerce`
- Feld: "Support Email"
- Beispiel: `privacy@ihre-firma.ch` oder `datenschutz@ihre-firma.de`

Persist configuration changes.

---

### Step 5: Automated Cleanup Scheduling

Navigate to: `System → Scheduled Tasks → New`

1. Select task type: **J2Commerce - Automatic Data Cleanup**
2. Configure execution parameters:
   - **Execution Frequency:** Daily
   - **Execution Time:** 02:00 (recommended for minimal system load)
   - **Status:** Enabled
3. Save configuration

---

### Implementation Verification

Validate the implementation using the following test procedure:

1. Create a test product with Custom Field `Lifetime License` set to `Yes`
2. Generate a test order for the configured product
3. Initiate a data removal request via `Users → Privacy → Requests → New Request`
4. Attempt data deletion via `Complete Request → Delete Data`
5. **Expected Result:** System blocks deletion and displays retention notification

Successful display of the retention notification confirms correct implementation.

---

## Features Overview

### Core Capabilities

- **Regulatory Compliance Export:** XML-formatted data exports compliant with GDPR Article 15 (Right of Access)
- **Automated Data Lifecycle Management:** Scheduled anonymization upon retention period expiration
- **Perpetual License Handling:** Selective data retention for software license reactivation requirements
- **Flexible Retention Frameworks:** Configurable retention periods (1-30 years) supporting multiple jurisdictions
- **Multi-Jurisdictional Support:** Configurable legal basis documentation for international operations
- **Automated Compliance Processing:** Scheduled task execution for expired data handling
- **Internationalization:** Multi-language interface support (German-CH, English-GB, French-CH)
- **Platform Integration:** Optional Joomla core user data inclusion in privacy exports

### Limitations

- **Recurring Subscriptions:** Automated subscription lifecycle management not included in current version
- **Payment Gateway Integration:** Real-time subscription status verification requires custom implementation
- **Subscription State Management:** Active subscription handling requires manual administrative processes

### Data Handling

**What gets exported:**
- J2Store orders and order items
- Billing and shipping addresses
- Joomla user account (optional)
- User profile data (optional)
- Activity logs (optional)

**What gets anonymized:**
- Email address → `anonymized@example.com`
- Name → `Anonymized User`
- Phone → `000-000-0000`
- Addresses → (deleted)

**What stays (anonymized):**
- Order numbers
- Order dates
- Order amounts
- Product information

**Exception for Lifetime Licenses:**
- Email address preserved (for license activation)
- All other data anonymized

---

## How It Works

### User Workflow

```
1. User requests data deletion
   ↓
2. System checks: Has orders within retention period?
   ↓
3a. NO → Immediate deletion/anonymization
3b. YES → Deletion blocked, show retention message
   ↓
4. After retention period expires
   ↓
5. Scheduled task automatically anonymizes data
   ↓
6. Exception: Lifetime licenses keep email for activation
```

### Retention Logic

**For ALL orders:**
- Retention period: Configurable (default 10 years)
- Applies to: ALL orders, not just licenses
- Legal basis: Accounting requirements (e.g., Swiss OR Art. 958f)

**For Lifetime Licenses:**
- After accounting retention expires (10 years)
- Partial anonymization: Keep email, delete everything else
- Reason: License activation requires email
- Legal basis: GDPR Art. 6 Abs. 1 lit. b (Contract fulfillment)

### Automatic Cleanup

**Scheduled Task runs daily:**
1. Finds users with all orders older than retention period
2. Checks for lifetime licenses
3. Full anonymization: No lifetime licenses
4. Partial anonymization: Has lifetime licenses (keep email)
5. Logs all actions

---

## Lifetime License Detection

### How It Works

**Detection Method:** J2Store Custom Field

The plugin checks the J2Store Custom Field `is_lifetime_license` for each product:

```sql
SELECT field_value 
FROM #__j2store_product_customfields 
WHERE product_id = ? 
AND field_name = 'is_lifetime_license'
```

**Result:**
- `field_value = 'Yes'` → Lifetime License
- `field_value = 'No'` → Regular Product
- `field_value = NULL` → Regular Product (field not set)

### Technical Implementation Rationale

**Previous Implementation (Deprecated):** Heuristic keyword detection in product metadata
- Unreliable due to typographical variations
- Lacked explicit configuration control
- Language-dependent pattern matching
- Prone to false positive classifications

**Current Implementation:** Structured metadata via J2Store Custom Fields
- Explicit boolean classification per product
- Eliminates ambiguity in product type determination
- Language-agnostic implementation
- Provides clear administrative visibility

### Product Type Support Matrix

**Supported:**
- One-time purchase transactions with standard retention
- Perpetual software licenses with extended retention

**Not Supported:**
- Recurring subscription products with active lifecycle management
- Real-time subscription status verification

**Note:** Organizations requiring subscription handling should implement manual review processes or contact Advans IT Solutions for custom development.

### Configuration

**For each Lifetime License product:**

1. Open product in J2Store
2. Find "Custom Fields" section
3. Set "Lifetime License" to "Yes"
4. Save

**That's it!** No code changes, no database modifications needed.

---

## Usage Guide

### Data Export

**User Action:**
```
Users → Privacy → Requests → New Request
Type: Export
Email: user@example.com
```

**Result:**
- XML file with all user data
- Includes J2Store orders, addresses
- Optional: Joomla user account data
- Download link sent via email

---

### Data Deletion

#### Scenario 1: User without orders

**Result:** • Immediate deletion

```
User requests deletion
  ↓
No orders found
  ↓
Data immediately anonymized
```

---

#### Scenario 2: User with recent orders

**Result:** • Deletion blocked

```
User requests deletion
  ↓
Orders found (e.g., 3 years old)
  ↓
Retention: 10 years → 7 years remaining
  ↓
Deletion blocked with message
```

**Error Message:**
```
═══════════════════════════════════════════════════════
DATENLÖSCHUNG DERZEIT NICHT MÖGLICH
═══════════════════════════════════════════════════════

Ihre Daten können derzeit nicht gelöscht werden, da Sie
Bestellungen getätigt haben, für die eine gesetzliche
Aufbewahrungspflicht besteht.

IHRE BESTELLUNGEN:
1. Bestellung #123
   Datum: 15.03.2020
   Betrag: 99.00 CHF
   Aufbewahrung bis: 15.03.2030
   Verbleibend: 7.0 Jahre

AUTOMATISCHE LÖSCHUNG:
• Ihre Daten werden AUTOMATISCH gelöscht ab: 15.03.2030
• Sie müssen NICHTS weiter tun
```

---

#### Scenario 3: User with old orders (retention expired)

**Result:** • Automatic deletion

```
Scheduled task runs daily (02:00)
  ↓
Finds users with orders older than 10 years
  ↓
Checks for lifetime licenses
  ↓
No lifetime licenses → Full anonymization
Has lifetime licenses → Partial anonymization (keep email)
```

---

#### Scenario 4: User with Lifetime License

**Result:** Partial anonymization

```
After 10 years:
  ↓
Accounting retention expired
  ↓
Has lifetime license
  ↓
Partial anonymization:
  • Email preserved (for license activation)
  • Name anonymized
  • Address deleted
  • Phone anonymized
```

**Error Message:**
```
═══════════════════════════════════════════════════════
LIFETIME-LIZENZEN (Buchhaltungsfrist abgelaufen)
═══════════════════════════════════════════════════════

WAS WIRD GESPEICHERT?

Für die Lizenzaktivierung notwendig:
• E-Mail-Adresse (für Aktivierung)
• Lizenzschlüssel
• Kaufdatum

Bereits gelöscht/anonymisiert:
• Vollständiger Name
• Rechnungsadresse
• Telefonnummer
```

---

## Administrator Guide

### Where to See Retention Messages

**Location 1: Flash Message (Immediate)**
- Appears at top of screen after clicking "Delete Data"
- Red error message with full details
- Shows retention period, orders, automatic deletion date

**Location 2: Action Log (Permanent)**
- Visible in request detail view
- `Users → Privacy → Requests → [Click Request]`
- Under "Action Log" section

**Location 3: Request Status**
- Request remains in "Confirmed" status if blocked
- Changes to "Complete" when deletion succeeds

---

### Managing Requests

**View all requests:**
```
Users → Privacy → Requests
```

**Process a request:**
1. Click on request
2. Review user information
3. Click "Complete Request"
4. Choose action:
   - Export Data → Generates XML
   - Delete Data → Checks retention, then deletes/blocks

---

### Monitoring Automatic Cleanup

**View scheduled task:**
```
System → Scheduled Tasks → J2Commerce - Automatic Data Cleanup
```

**View logs:**
```
System → Scheduled Tasks → [Task] → View Logs
```

**Example log:**
```
[2025-01-15 02:00:15] Starting automatic data cleanup...
[2025-01-15 02:00:15] Retention period: 10 years
[2025-01-15 02:00:16] Found 10 users with expired retention
[2025-01-15 02:00:16] Fully anonymized: 8 users
[2025-01-15 02:00:16] Partially anonymized: 2 users (lifetime licenses)
[2025-01-15 02:00:17] Cleanup complete: 0 errors
```

---

## Workflow Examples

### Example 1: Regular Product, Recent Order

**Situation:**
- User purchased regular product 3 years ago
- Retention: 10 years
- Remaining: 7 years

**User Action:** Requests deletion

**System Response:**
```
• Deletion blocked
Message: "Automatische Löschung ab: 15.03.2030"
Status: Confirmed (not Complete)
```

**What happens:**
- Data stays until 2030
- Automatic cleanup deletes in 2030
- User doesn't need to do anything

---

### Example 2: Lifetime License, Old Order

**Situation:**
- User purchased lifetime license 11 years ago
- Retention: 10 years (expired)
- License: Lifetime

**User Action:** Requests deletion

**System Response:**
```
• Deletion blocked (partial)
Message: "Email wird für Lizenzaktivierung benötigt"
Status: Confirmed
```

**What happens:**
- Automatic cleanup runs
- Name, address, phone → anonymized
- Email → preserved
- User can still activate license

---

### Example 3: No Orders

**Situation:**
- User registered but never ordered
- No orders in database

**User Action:** Requests deletion

**System Response:**
```
• Deletion successful
Status: Complete
```

**What happens:**
- Immediate deletion
- All data anonymized
- No retention applies

---

## Configuration

### Plugin Settings

**Access:** `System → Plugins → Privacy - J2Commerce`

#### Basic Settings

| Setting | Default | Description |
|---------|---------|-------------|
| Include Joomla Core Data | Yes | Include user account, profile, logs in export |
| Anonymize Orders | Yes | Anonymize instead of delete orders |
| Delete Addresses | Yes | Delete saved addresses on removal |

#### Retention Settings

| Setting | Default | Description |
|---------|---------|-------------|
| Retention Period (Years) | 10 | Legal retention requirement (1-30 years) |
| Legal Basis | (empty) | Legal grounds shown in error messages |
| Support Email | support@example.com | Contact for data deletion inquiries |

---

### Scheduled Task Settings

**Access:** `System → Scheduled Tasks → J2Commerce - Automatic Data Cleanup`

**Recommended Settings:**
- **Frequency:** Daily
- **Time:** 02:00 (2 AM, low traffic)
- **Enabled:** Yes

**What it does:**
- Finds users with expired retention
- Anonymizes data automatically
- Logs all actions
- Handles lifetime licenses correctly

---

## Legal Basis Examples

### Switzerland

```
• Switzerland: OR Art. 958f (10 years)
```

**Retention Years:** 10

---

### Germany

```
• Germany: AO §147 (10 years)
• Germany: HGB §257 (10 years)
```

**Retention Years:** 10

---

### Austria

```
• Austria: BAO §132 (7 years)
• Austria: UGB §212 (7 years)
```

**Retention Years:** 7

---

### France

```
• France: Code de commerce L123-22 (10 ans)
```

**Retention Years:** 10

---

### Spain

```
• España: Código de Comercio Art. 30 (6 años)
```

**Retention Years:** 6

---

### United Kingdom

```
• UK: Companies Act 2006 (6 years)
• UK: HMRC requirements (6 years)
```

**Retention Years:** 6

---

### USA

```
• USA: IRS regulations (7 years)
```

**Retention Years:** 7

---

### Multi-Country (EU)

```
• EU: GDPR Art. 6 Abs. 1 lit. c
• Germany: AO §147 (10 years)
• France: Code de commerce L123-22 (10 ans)
• Austria: BAO §132 (7 years)
```

**Retention Years:** 10 (use longest period)

---

### DACH Region

```
• Switzerland: OR Art. 958f (10 years)
• Germany: AO §147 (10 years)
• Austria: BAO §132 (7 years)
```

**Retention Years:** 10

---

## Multi-Language Support

### Supported Languages

- • **German (Switzerland)** - `de-CH`
- • **English (UK)** - `en-GB`
- • **French (France)** - `fr-FR`

### Adding New Languages

**Step 1: Create language folder**
```bash
mkdir -p language/it-CH
```

**Step 2: Copy files**
```bash
cp language/en-GB/plg_privacy_j2commerce.ini language/it-CH/
cp language/en-GB/plg_privacy_j2commerce.sys.ini language/it-CH/
cp language/en-GB/index.html language/it-CH/
```

**Step 3: Translate**
Open `language/it-CH/plg_privacy_j2commerce.ini` and translate all strings.

**Step 4: Reinstall plugin**
```bash
./build.sh
# Install ZIP in Joomla
```

**Note:** Error messages are currently in German. For full multi-language support, contact support.

---

## J2Store Custom Fields

### Why Custom Fields?

**The plugin uses J2Store's built-in Custom Fields feature to detect Lifetime Licenses.**

**Advantages:**
- • No J2Store code modifications needed
- • Explicit configuration per product
- • No typos or false positives
- • Clear Yes/No choice
- • Visible in product overview

### Setup

**See [Quick Setup Guide](#quick-setup-guide) Step 1**

### Technical Details

**Database Table:** `#__j2store_product_customfields`

**Structure:**
```sql
CREATE TABLE `#__j2store_product_customfields` (
  `j2store_customfield_id` int(11) NOT NULL AUTO_INCREMENT,
  `product_id` int(11) NOT NULL,
  `field_name` varchar(255) NOT NULL,
  `field_value` text,
  PRIMARY KEY (`j2store_customfield_id`)
);
```

**Example Data:**
```sql
INSERT INTO `#__j2store_product_customfields` VALUES
(1, 123, 'is_lifetime_license', 'Yes'),
(2, 456, 'is_lifetime_license', 'No');
```

**Query:**
```sql
SELECT field_value 
FROM #__j2store_product_customfields 
WHERE product_id = 123 
AND field_name = 'is_lifetime_license';
-- Result: 'Yes'
```

---

## Testing

### Test Coverage Status

**Current Status:** Automated tests are placeholder implementations only.

**Test Coverage:** ~10% (installation verification only)

**Recommended Approach:**
1. Use manual testing checklist: `tests/MANUAL_TESTING.md`
2. test implementation planned: `tests/TODO_TESTS.md`
3. Estimated effort for full test suite: 24-32 hours

### Manual Testing

Complete manual testing checklist available in `tests/MANUAL_TESTING.md`.

**Quick Manual Test:**

**1. Create test product:**
```
Components → J2Store → Products → New
Name: Test Lifetime Product
Custom Fields → Lifetime License: Yes
Save
```

**2. Create test order:**
- Create order for test user
- Use the test product
- Complete order

**3. Request deletion:**
```
Users → Privacy → Requests → New Request
Type: Remove
Email: testuser@example.com
```

**4. Try to delete:**
```
Users → Privacy → Requests → [Open request]
Complete Request → Delete Data
```

**5. Expected result:**
```
• Error message appears
Shows: "LIFETIME-LIZENZEN"
Lists: Test Lifetime Product
```

• **If you see this, the plugin works correctly!**

---

### Automated Testing

**Run integration tests:**
```bash
cd tests/integration
./run-tests.sh
```

**Test suites:**
1. Installation - Plugin registration
2. Uninstall - Clean removal

**Port:** 8082

---

## Development

### Structure

```
plg_privacy_j2commerce/
├── README.md                    # This file
├── VERSION
├── LICENSE.txt
├── j2commerce.xml              # Plugin manifest
├── script.php                  # Installation script
├── services/
│   └── provider.php            # Service provider
├── src/
│   ├── Extension/
│   │   └── J2Commerce.php      # Main plugin class
│   └── Task/
│       └── AutoCleanupTask.php # Scheduled task
├── language/
│   ├── de-CH/                  # German (Switzerland)
│   ├── en-GB/                  # English (UK)
│   └── fr-FR/                  # French (France)
└── tests/                      # Integration tests
```

### Building

```bash
./build.sh
```

Creates: `plg_privacy_j2commerce.zip`

### Key Classes

**J2Commerce.php:**
- `onPrivacyExportRequest()` - Export user data
- `onPrivacyCanRemoveData()` - Check if deletion allowed
- `onPrivacyRemoveData()` - Perform deletion/anonymization
- `checkOrderRetention()` - Check retention periods
- `isLifetimeLicense()` - Check custom field
- `formatRetentionMessage()` - Generate error message

**AutoCleanupTask.php:**
- `autoCleanup()` - Main cleanup logic
- `hasLifetimeLicense()` - Check for lifetime licenses
- `partialAnonymizeUserData()` - Keep email, delete rest
- `anonymizeUserData()` - Full anonymization

---

## Support

### Documentation Files

All documentation is in this README. Previous separate files have been consolidated.

### Common Issues

**Issue: Custom Field not visible**
- Check: Field is Published
- Check: Display in = Product
- Clear cache: System → Clear Cache

**Issue: Lifetime products not detected**
- Check: Field name is exactly `is_lifetime_license`
- Check: Field value is `Yes` (not `yes` or `1`)
- Check: Product saved after setting field

**Issue: Scheduled task not running**
- Check: Joomla Cron configured
- Test: Run task manually
- Check: Task logs for errors

**Issue: Subscription-based products**
- **Status:** Recurring subscription lifecycle management not included in current version
- **Recommended Approach:** Apply standard accounting retention periods to subscription orders
- **Enterprise Requirements:** Contact Advans IT Solutions for custom subscription handling implementation

### Contact

**Advans IT Solutions GmbH**  
Karl-Barth-Platz 9  
4052 Basel  
Switzerland  
CHE-316.407.165

**Website:** https://advans.ch

---

## License

Proprietary software. Copyright (C) 2025 Advans IT Solutions GmbH. All rights reserved.

---

## Changelog

### Version 1.0.0 (2025-01-11)

**Initial Release:**
- GDPR-compliant data export
- Automatic data anonymization
- Lifetime license support via Custom Fields
- One-time purchase handling
- Configurable retention periods
- Multi-country legal basis support
- Scheduled automatic cleanup
- Multi-language support (de-CH, en-GB, fr-FR)
- documentation

**Known Limitations:**
- Automated recurring subscription lifecycle management not included
- Active subscription status verification requires manual administrative processes
- Payment gateway integration for real-time subscription status not implemented

---

**End of Documentation**
