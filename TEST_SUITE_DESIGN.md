# Comprehensive Test Suite Design

## Overview

This document defines the comprehensive test suites for all J2Commerce extensions. Each extension will have functional tests covering all features, not just installation/uninstallation.

---

## 1. PLG_SYSTEM_J2COMMERCE_2FA

### Test Suites (7 total)

1. **01-installation.php** ✅ (exists)
   - Plugin files installed
   - Database entry in `#__extensions`
   - Service provider registered
   - Event subscriptions verified

2. **02-configuration.php** (NEW)
   - All 5 parameters accessible
   - Default values correct
   - Parameter validation (ranges, types)
   - Session timeout range (300-86400)

3. **03-session-preservation.php** (NEW)
   - Create mock J2Store session data
   - Simulate 2FA login
   - Verify cart preserved
   - Verify shipping info preserved
   - Verify payment info preserved
   - Verify return URL maintained

4. **04-guest-cart-transfer.php** (NEW)
   - Create guest cart with items
   - Simulate user login
   - Verify cart transferred to user
   - Verify cart items merged
   - Verify quantities summed for duplicates
   - Verify guest cart deleted

5. **05-session-security.php** (NEW)
   - Verify session ID regenerated after 2FA
   - Verify old session data preserved
   - Verify session timeout respected

6. **06-debug-mode.php** (NEW)
   - Enable debug mode
   - Trigger login event
   - Verify debug messages logged
   - Disable debug mode
   - Verify no messages logged

7. **07-uninstall.php** ✅ (exists)
   - Plugin removed from database
   - No orphaned data

---

## 2. PLG_J2COMMERCE_ACYMAILING

### Test Suites (9 total)

1. **01-installation.php** ✅ (exists)
   - Plugin files installed
   - AcyMailing dependency check
   - Template files present

2. **02-configuration.php** (NEW)
   - All 9 parameters accessible
   - List ID validation
   - Multiple lists parsing

3. **03-checkout-integration.php** (NEW)
   - Checkbox appears in checkout
   - Checkbox label customizable
   - Default state (checked/unchecked)
   - Guest users can subscribe

4. **04-product-page-integration.php** (NEW)
   - Checkbox appears on product pages when enabled
   - Checkbox hidden when disabled

5. **05-subscription-processing.php** (NEW)
   - Manual subscription (checkbox checked)
   - Auto-subscribe mode
   - Guest user subscription
   - Registered user subscription
   - Multiple list subscription

6. **06-acymailing-api.php** (NEW)
   - User created in AcyMailing
   - Subscription status correct
   - Double opt-in email sent
   - List assignment correct

7. **07-order-state.php** (NEW)
   - Only confirmed orders processed
   - Pending orders ignored
   - Failed orders ignored

8. **08-error-handling.php** (NEW)
   - AcyMailing not installed scenario
   - Invalid list ID
   - Empty email address
   - API errors handled gracefully

9. **09-uninstall.php** ✅ (exists)
   - Plugin removed
   - No orphaned data

---

## 3. PLG_J2COMMERCE_PRODUCTCOMPARE

### Test Suites (10 total)

1. **01-installation.php** ✅ (exists)
   - Plugin files installed
   - Media files in correct location
   - Service provider registered

2. **02-configuration.php** (NEW)
   - All 5 parameters accessible
   - Max products range validation (2-10)
   - Button class customization

3. **03-button-display.php** (NEW)
   - Button appears in product lists
   - Button appears on detail pages
   - Button text customizable
   - Button CSS classes applied

4. **04-comparison-bar.php** (NEW)
   - Bar appears when products added
   - Bar hidden when empty
   - Product thumbnails displayed
   - Remove button functional
   - Clear all button functional

5. **05-ajax-comparison.php** (NEW)
   - Comparison data fetched correctly
   - HTML table generated
   - Product attributes displayed
   - Error handling for failed requests
   - Minimum 2 products required

6. **06-database-queries.php** (NEW)
   - Product data retrieved correctly
   - Variant data joined properly
   - Content titles fetched
   - Product options/attributes loaded
   - Only enabled products shown

7. **07-javascript-functionality.php** (NEW)
   - Add/remove toggle works
   - Button state updates
   - Multiple products can be added
   - Max products limit enforced

8. **08-modal-display.php** (NEW)
   - Modal opens on "View Comparison"
   - Modal closes on X button
   - Loading indicator shown

9. **09-responsive-design.php** (NEW)
   - Mobile layout functional
   - Tablet layout functional
   - Desktop layout functional

10. **10-uninstall.php** ✅ (exists)
    - Plugin removed
    - Media files remain (manual cleanup)
    - No orphaned data

---

## 4. PLG_PRIVACY_J2COMMERCE

### Test Suites (10 total)

1. **01-installation.php** ✅ (exists)
   - Plugin files installed
   - Extends PrivacyPlugin correctly
   - Service provider registered

2. **02-configuration.php** (NEW)
   - All 3 parameters accessible
   - Default values correct

3. **03-data-export-orders.php** (NEW)
   - Order data exported correctly
   - Order items included
   - Currency and totals correct
   - Dates formatted properly

4. **04-data-export-addresses.php** (NEW)
   - Billing addresses exported
   - Shipping addresses exported
   - Address fields complete
   - Multiple orders handled

5. **05-data-export-joomla.php** (NEW)
   - User account data exported
   - Profile fields exported
   - Action logs exported
   - Optional inclusion works

6. **06-xml-format.php** (NEW)
   - Export request creates all domains
   - XML format valid
   - All domains present

7. **07-data-anonymization.php** (NEW)
   - Names replaced with "Anonymized User"
   - Email replaced with "anonymized@example.com"
   - Phone numbers cleared
   - Addresses replaced with "Anonymized"
   - Zip codes replaced with "00000"

8. **08-data-removal.php** (NEW)
   - Anonymization replaces personal data
   - Order history preserved
   - Addresses anonymized
   - User ID retained for referential integrity

9. **09-privacy-compliance.php** (NEW)
   - GDPR right to access fulfilled
   - GDPR right to erasure fulfilled
   - Data minimization respected
   - Audit trail maintained

10. **10-uninstall.php** ✅ (exists)
    - Plugin removed
    - No orphaned data

---

## 5. COM_J2STORE_CLEANUP

### Test Suites (10 total)

1. **01-installation.php** ✅ (exists)
   - Component files installed
   - Menu entry created
   - Language files loaded
   - Access permissions set

2. **02-scanning.php** ✅ (exists)
   - Disabled extensions detected
   - Old version components detected
   - Core J2Store excluded
   - Cleanup tool excluded
   - Version comparison works

3. **03-cleanup.php** ✅ (exists)
   - Selected extensions removed
   - Extension files remain
   - Success message displayed
   - Extension count updated

4. **04-display.php** (NEW)
   - Extension list loads
   - All J2Store/J2Commerce extensions shown
   - Extension details displayed
   - Incompatible extensions highlighted
   - Compatible extensions shown normally

5. **05-ui-elements.php** (NEW)
   - Dark theme applied
   - Table formatting correct
   - Checkboxes only on incompatible extensions
   - "Select All" checkbox works
   - Buttons styled correctly
   - Responsive layout

6. **06-safety-checks.php** (NEW)
   - Cannot remove enabled extensions
   - Cannot remove core J2Store
   - Cannot remove cleanup tool itself
   - Backup warning displayed
   - Confirmation required

7. **07-database-operations.php** (NEW)
   - Extensions queried correctly
   - Manifest cache parsed (JSON)
   - Version extracted from manifest
   - DELETE query executes safely
   - Transaction handling

8. **08-language-support.php** (NEW)
   - English (en-CH) strings loaded
   - German (de-CH) strings loaded
   - French (fr-FR) strings loaded
   - Fallback to English

9. **09-security.php** (NEW)
   - CSRF token validation
   - Admin-only access
   - SQL injection prevention
   - XSS prevention

10. **04-uninstall.php** ✅ (exists - rename to 10)
    - Component removed
    - Menu entry removed
    - No orphaned data

---

## 6. COM_J2COMMERCE_IMPORTEXPORT

### Test Suites (10 total) - ✅ ALL EXIST

1. **01-installation.php** ✅
2. **02-frontend.php** ✅
3. **03-backend.php** ✅
4. **04-api.php** ✅
5. **05-database.php** ✅
6. **06-j2commerce.php** ✅
7. **07-uninstall.php** ✅
8. **08-multilingual.php** ✅
9. **09-security.php** ✅
10. **10-performance.php** ✅

---

## Implementation Priority

### Phase 1: Critical Functionality (Week 1)
- PLG_SYSTEM_J2COMMERCE_2FA: Tests 02-06
- PLG_J2COMMERCE_ACYMAILING: Tests 02-05
- PLG_J2COMMERCE_PRODUCTCOMPARE: Tests 02-05

### Phase 2: Advanced Features (Week 2)
- PLG_PRIVACY_J2COMMERCE: Tests 02-09
- COM_J2STORE_CLEANUP: Tests 04-09
- PLG_J2COMMERCE_ACYMAILING: Tests 06-08
- PLG_J2COMMERCE_PRODUCTCOMPARE: Tests 06-09

### Phase 3: Polish & Edge Cases (Week 3)
- All remaining tests
- Error handling refinement
- Performance optimization
- Documentation updates

---

## Test Execution Strategy

### Local Development
```bash
cd J2Commerce/{extension}/tests
docker compose up -d
sleep 60
./run-tests.sh all
docker compose down -v
```

### CI/CD (GitHub Actions)
- Automatic execution on push
- Logs committed to `tests/logs/{extension}/`
- Artifacts available for 90 days
- Branch protection with temporary branches

### Test Result Format
Each test produces a `.txt` file with:
- Test name and timestamp
- Pass/fail status for each check
- Detailed error messages
- Summary statistics

---

## Success Criteria

Each extension must have:
1. ✅ 100% feature coverage
2. ✅ All configuration options tested
3. ✅ Database operations verified
4. ✅ Error handling validated
5. ✅ Security checks passed
6. ✅ Integration points tested
7. ✅ UI/UX elements verified (if applicable)
8. ✅ Performance benchmarks met (if applicable)
9. ✅ Clean installation/uninstallation
10. ✅ No orphaned data after uninstall

---

## Notes

- All tests use the SwissQRCode pattern (no Factory::getApplication() to avoid output buffering)
- Tests are idempotent and can run multiple times
- Each test is independent and doesn't rely on previous tests
- Mock data is created and cleaned up within each test
- Database transactions used where possible for isolation
