#!/bin/bash
#
# Package verification script

set -e

PACKAGE="../packages/plg_privacy_j2commerce.zip"
TEMP_DIR=$(mktemp -d)

echo "🔍 Verifying package: ${PACKAGE}"
echo ""

if [ ! -f "${PACKAGE}" ]; then
    echo "❌ Package not found: ${PACKAGE}"
    echo "   Run ./build.sh first"
    exit 1
fi
echo "✅ Package exists"

if ! unzip -t "${PACKAGE}" > /dev/null 2>&1; then
    echo "❌ Package is not a valid ZIP file"
    exit 1
fi
echo "✅ Package is valid ZIP"

unzip -q "${PACKAGE}" -d "${TEMP_DIR}"
cd "${TEMP_DIR}"

if [ ! -f "j2commerce.xml" ]; then
    echo "❌ Manifest file not found"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Manifest exists"

if ! grep -q '<?xml version' j2commerce.xml; then
    echo "❌ Manifest is not valid XML"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Manifest is valid XML"

if ! grep -q 'type="plugin"' j2commerce.xml; then
    echo "❌ Not a plugin extension"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

if ! grep -q 'group="privacy"' j2commerce.xml; then
    echo "❌ Wrong plugin group (should be privacy)"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Plugin type and group correct"

REQUIRED_FILES=(
    "services/provider.php"
    "src/Extension/J2Commerce.php"
    "language/en-GB/plg_privacy_j2commerce.ini"
    "language/en-GB/plg_privacy_j2commerce.sys.ini"
    "language/de-DE/plg_privacy_j2commerce.ini"
    "language/de-DE/plg_privacy_j2commerce.sys.ini"
)

MISSING_FILES=0
for file in "${REQUIRED_FILES[@]}"; do
    if [ ! -f "${file}" ]; then
        echo "❌ Missing required file: ${file}"
        MISSING_FILES=$((MISSING_FILES + 1))
    fi
done

if [ ${MISSING_FILES} -eq 0 ]; then
    echo "✅ All required files present (${#REQUIRED_FILES[@]} files)"
else
    echo "❌ ${MISSING_FILES} required files missing"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

echo ""
echo "🔍 Checking PHP files..."
PHP_COUNT=$(find . -name "*.php" | wc -l)
echo "✅ Found ${PHP_COUNT} PHP files"

echo ""
echo "🔍 Checking namespace declarations..."
if ! grep -q "namespace Advans\\\\Plugin\\\\Privacy\\\\J2Commerce" src/Extension/J2Commerce.php; then
    echo "❌ Invalid namespace in J2Commerce.php"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Namespace declarations correct"

echo ""
echo "🔍 Checking Privacy API usage..."
if ! grep -q "extends PrivacyPlugin" src/Extension/J2Commerce.php; then
    echo "❌ Plugin does not extend PrivacyPlugin"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

if ! grep -q "onPrivacyExportRequest" src/Extension/J2Commerce.php; then
    echo "❌ Missing onPrivacyExportRequest method"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

if ! grep -q "onPrivacyRemoveData" src/Extension/J2Commerce.php; then
    echo "❌ Missing onPrivacyRemoveData method"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Privacy API methods implemented"

rm -rf "${TEMP_DIR}"

echo ""
echo "✅ Package verification complete"
echo ""
echo "Package is ready for installation:"
echo "  Joomla Backend → Extensions → Install"
echo "  Upload: ${PACKAGE}"
echo ""
echo "After installation:"
echo "  1. Enable plugin: Extensions → Plugins → Privacy - J2Commerce"
echo "  2. Configure settings"
echo "  3. Test via: Users → Privacy → Export/Remove Data"
