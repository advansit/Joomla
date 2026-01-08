# Vollständiger Implementierungsplan: Test-System nach SwissQRCode-Pattern

## Problem-Analyse

**Aktueller Zustand:**
- 47 Test-Skripte existieren, aber schlagen fehl (HTTP_HOST Fehler)
- Workflows haben nur 1 Job statt Matrix-Strategy
- Logs werden auf separate Branches committed (Branch Protection)
- Tests sind nicht nach SwissQRCode-Pattern strukturiert

**Ziel-Zustand:**
- Alle Tests nach SwissQRCode-Pattern (Klassen, strukturiertes Output)
- Matrix-Strategy in Workflows (jeder Test = separater Job)
- Logs direkt auf main committed
- Alle 47 Tests müssen PASS sein

## Phase 1: Test-Skripte umschreiben (KRITISCH)

### Pattern von SwissQRCode:

```php
<?php
define('_JEXEC', 1);
define('JPATH_BASE', '/var/www/html');

require_once JPATH_BASE . '/includes/defines.php';
require_once JPATH_BASE . '/includes/framework.php';

use Joomla\CMS\Factory;

class TestNameTest
{
    private $db;

    public function __construct()
    {
        $this->db = Factory::getDbo();
    }

    public function run(): bool
    {
        echo "=== Test Name ===\n\n";

        $allPassed = true;
        $allPassed = $this->testMethod1() && $allPassed;
        $allPassed = $this->testMethod2() && $allPassed;

        $this->printSummary();
        return $allPassed;
    }

    private function testMethod1(): bool
    {
        echo "Test: Description... ";
        
        // Test logic
        
        if ($success) {
            echo "✅ PASS\n";
            return true;
        }
        
        echo "❌ FAIL (reason)\n";
        return false;
    }

    private function printSummary(): void
    {
        echo "\n=== Test Summary ===\n";
        echo "All tests completed.\n";
    }
}

$test = new TestNameTest();
$result = $test->run();
exit($result ? 0 : 1);
```

### Zu ändernde Dateien (47 Stück):

**plg_system_j2commerce_2fa (7):**
1. `01-installation.php` - Plugin-Installation
2. `02-configuration.php` - Parameter-Tests
3. `03-session-preservation.php` - Session-Daten nach 2FA
4. `04-guest-cart-transfer.php` - Warenkorb-Transfer
5. `05-session-security.php` - Session-Sicherheit
6. `06-debug-mode.php` - Debug-Modus
7. `07-uninstall.php` - Deinstallation

**plg_j2commerce_acymailing (7):**
1. `01-installation.php`
2. `02-configuration.php`
3. `03-acymailing-integration.php`
4. `04-event-subscriptions.php`
5. `05-subscription-logic.php`
6. `06-error-handling.php`
7. `07-uninstall.php`

**plg_j2commerce_productcompare (7):**
1. `01-installation.php`
2. `02-configuration.php`
3. `03-media-files.php`
4. `04-database-structure.php`
5. `05-ajax-endpoint.php`
6. `06-javascript-functionality.php`
7. `07-uninstall.php`

**plg_privacy_j2commerce (7):**
1. `01-installation.php`
2. `02-configuration.php`
3. `03-privacy-plugin-base.php`
4. `04-data-export.php`
5. `05-data-anonymization.php`
6. `06-gdpr-compliance.php`
7. `07-uninstall.php`

**com_j2store_cleanup (9):**
1. `01-installation.php`
2. `02-scanning.php`
3. `03-cleanup.php`
4. `04-ui-elements.php`
5. `05-security.php`
6. `06-display-functionality.php`
7. `07-safety-checks.php`
8. `08-language-support.php`
9. `09-uninstall.php`

**com_j2commerce_importexport (10):**
1. `01-installation.php`
2. `02-frontend.php`
3. `03-backend.php`
4. `04-api.php`
5. `05-database.php`
6. `06-j2commerce.php`
7. `07-uninstall.php`
8. `08-multilingual.php`
9. `09-security.php`
10. `10-performance.php`

### Wichtig: Alte Dateinamen umbenennen

Alle bestehenden Test-Skripte haben falsche Namen:
- `01-installation-verification.php` → `01-installation.php`
- `02-uninstall-verification.php` → `07-uninstall.php` (oder 09/10 je nach Extension)

## Phase 2: Workflows umbauen (KRITISCH)

### Pattern von SwissQRCode:

```yaml
name: Test Extension

on:
  workflow_dispatch:
  push:
    branches: [main]
    paths: ['Extension/path/**']

jobs:
  build:
    name: Build Package
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v6
      - name: Build
        run: |
          cd Extension/path
          ./build.sh
          cp *.zip tests/extension.zip
      - uses: actions/upload-artifact@v6
        with:
          name: extension-package
          path: Extension/path/tests/extension.zip

  test:
    name: ${{ matrix.name }}
    runs-on: ubuntu-latest
    needs: build
    strategy:
      fail-fast: false
      max-parallel: 1
      matrix:
        include:
          - name: '01 - Installation'
            script: 'installation'
          - name: '02 - Test Name'
            script: 'testname'
          # ... alle Tests
    
    steps:
      - uses: actions/checkout@v6
      - uses: actions/download-artifact@v6
        with:
          name: extension-package
          path: Extension/path/tests/
      - name: Start environment
        run: |
          cd Extension/path/tests
          docker compose up -d
          sleep 60
      - name: Run tests
        continue-on-error: true
        run: |
          cd Extension/path/tests
          ./run-tests.sh ${{ matrix.script }}
      - uses: actions/upload-artifact@v6
        if: always()
        with:
          name: ${{ matrix.script }}-logs
          path: Extension/path/tests/test-results/*.txt
      - name: Cleanup
        if: always()
        run: |
          cd Extension/path/tests
          docker compose down -v

  collect-results:
    name: Collect Results
    runs-on: ubuntu-latest
    needs: test
    if: always()
    permissions:
      contents: write
    steps:
      - uses: actions/checkout@v6
        with:
          token: ${{ secrets.GITHUB_TOKEN }}
      - uses: actions/download-artifact@v6
        with:
          path: test-artifacts/
      - name: Collect logs
        run: |
          mkdir -p tests/logs/extension_name
          find test-artifacts/ -name "*.txt" -exec cp {} tests/logs/extension_name/ \;
      - name: Commit logs
        run: |
          git config user.name "GitHub Actions"
          git config user.email "actions@github.com"
          git add -f tests/logs/extension_name/*.txt
          git diff --staged --quiet || git commit -m "Add test logs [skip ci]"
          git push || true
```

### Zu ändernde Workflows (6):

1. `.github/workflows/j2commerce-2fa.yml`
2. `.github/workflows/j2commerce-acymailing.yml`
3. `.github/workflows/j2commerce-privacy.yml`
4. `.github/workflows/j2commerce-product-compare.yml`
5. `.github/workflows/j2store-cleanup.yml`
6. `.github/workflows/j2commerce-import-export.yml`

### Matrix-Einträge pro Workflow:

**j2commerce-2fa.yml:**
```yaml
matrix:
  include:
    - name: '01 - Installation'
      script: 'installation'
    - name: '02 - Configuration'
      script: 'configuration'
    - name: '03 - Session Preservation'
      script: 'session'
    - name: '04 - Guest Cart Transfer'
      script: 'cart'
    - name: '05 - Session Security'
      script: 'session'
    - name: '06 - Debug Mode'
      script: 'debug'
    - name: '07 - Uninstall'
      script: 'uninstall'
```

**j2commerce-acymailing.yml:**
```yaml
matrix:
  include:
    - name: '01 - Installation'
      script: 'installation'
    - name: '02 - Configuration'
      script: 'configuration'
    - name: '03 - AcyMailing Integration'
      script: 'integration'
    - name: '04 - Event Subscriptions'
      script: 'events'
    - name: '05 - Subscription Logic'
      script: 'subscription'
    - name: '06 - Error Handling'
      script: 'errors'
    - name: '07 - Uninstall'
      script: 'uninstall'
```

(Analog für alle anderen Workflows)

## Phase 3: run-tests.sh anpassen

Jedes `run-tests.sh` muss die richtigen Test-Skripte aufrufen:

```bash
case $test_suite in
    "installation")
        tests=("Installation:01-installation.php")
        ;;
    "configuration")
        tests=("Configuration:02-configuration.php")
        ;;
    # ... für jeden Test
    "all")
        tests=(
            "Installation:01-installation.php"
            "Configuration:02-configuration.php"
            # ... alle Tests
        )
        ;;
esac
```

## Phase 4: Branch Protection anpassen

**Option A:** Branch Protection Rules ändern
- Erlaube GitHub Actions Bot direkten Push auf main
- Settings → Branches → main → Edit → "Allow specified actors to bypass"

**Option B:** Workflows anpassen
- Verwende `git push || true` (ignoriert Fehler)
- Logs gehen verloren wenn Push fehlschlägt

**Empfehlung:** Option A

## Phase 5: Testen

1. Trigger alle Workflows manuell
2. Warte bis alle durchgelaufen sind (~10-15 Min pro Workflow)
3. Prüfe `tests/logs/` auf main
4. Analysiere jeden Log auf PASS/FAIL
5. Fixe fehlgeschlagene Tests
6. Wiederhole bis alle PASS

## Geschätzter Aufwand

- Phase 1 (Test-Skripte): **8-12 Stunden** (47 Dateien × 10-15 Min)
- Phase 2 (Workflows): **2-3 Stunden** (6 Dateien × 20-30 Min)
- Phase 3 (run-tests.sh): **1 Stunde** (6 Dateien × 10 Min)
- Phase 4 (Branch Protection): **5 Minuten**
- Phase 5 (Testen & Fixen): **4-8 Stunden** (mehrere Iterationen)

**GESAMT: 15-24 Stunden**

## Nächste Schritte

1. Beginne mit **einer** Extension (z.B. plg_system_j2commerce_2fa)
2. Schreibe alle 7 Test-Skripte nach Pattern um
3. Passe Workflow an
4. Teste vollständig
5. Wenn erfolgreich: Repliziere für andere 5 Extensions

## Automatisierung möglich?

Teilweise. Ein Script könnte:
- Test-Skript-Grundgerüste generieren
- Workflow-Dateien aus Template erstellen
- run-tests.sh automatisch anpassen

Aber: Test-Logik muss manuell implementiert werden (zu komplex für Automatisierung).
