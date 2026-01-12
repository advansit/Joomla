#!/bin/bash
#
# Package verification script
#
# Verifies that the package is properly structured and installable

set -e

PACKAGE="../packages/plg_j2commerce_acymailing.zip"
TEMP_DIR=$(mktemp -d)

echo "🔍 Verifying package: ${PACKAGE}"
echo ""

# Check package exists
if [ ! -f "${PACKAGE}" ]; then
    echo "❌ Package not found: ${PACKAGE}"
    echo "   Run ./build.sh first"
    exit 1
fi
echo "✅ Package exists"

# Check it's a valid ZIP
if ! unzip -t "${PACKAGE}" > /dev/null 2>&1; then
    echo "❌ Package is not a valid ZIP file"
    exit 1
fi
echo "✅ Package is valid ZIP"

# Extract to temp directory
unzip -q "${PACKAGE}" -d "${TEMP_DIR}"
cd "${TEMP_DIR}"

# Check manifest exists
if [ ! -f "acymailing.xml" ]; then
    echo "❌ Manifest file not found"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Manifest exists"

# Validate XML (basic check)
if ! grep -q '<?xml version' acymailing.xml; then
    echo "❌ Manifest is not valid XML"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Manifest is valid XML"

# Check plugin type and group
if ! grep -q 'type="plugin"' acymailing.xml; then
    echo "❌ Not a plugin extension"
    rm -rf "${TEMP_DIR}"
    exit 1
fi

if ! grep -q 'group="j2commerce"' acymailing.xml; then
    echo "❌ Wrong plugin group (should be j2commerce)"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Plugin type and group correct"

# Check required files
REQUIRED_FILES=(
    "services/provider.php"
    "src/Extension/AcyMailing.php"
    "tmpl/checkout.php"
    "language/en-GB/plg_j2commerce_acymailing.ini"
    "language/en-GB/plg_j2commerce_acymailing.sys.ini"
    "language/de-DE/plg_j2commerce_acymailing.ini"
    "language/de-DE/plg_j2commerce_acymailing.sys.ini"
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

# Check PHP files exist
echo ""
echo "🔍 Checking PHP files..."
PHP_COUNT=$(find . -name "*.php" | wc -l)
echo "✅ Found ${PHP_COUNT} PHP files"

# Check namespace declarations
echo ""
echo "🔍 Checking namespace declarations..."
if ! grep -q "namespace Advans\\\\Plugin\\\\J2Commerce\\\\AcyMailing" src/Extension/AcyMailing.php; then
    echo "❌ Invalid namespace in AcyMailing.php"
    rm -rf "${TEMP_DIR}"
    exit 1
fi
echo "✅ Namespace declarations correct"

# Cleanup
rm -rf "${TEMP_DIR}"

echo ""
echo "✅ Package verification complete"
echo ""
echo "Package is ready for installation:"
echo "  Joomla Backend → Extensions → Install"
echo "  Upload: ${PACKAGE}"
