#!/bin/bash

# J2Store Cleanup Build Script
# Copyright (C) 2025 Advans IT Solutions GmbH

echo "========================================="
echo "J2Store Cleanup Builder"
echo "========================================="
echo ""

VERSION="1.0.0"
PACKAGE_NAME="com_j2store_cleanup_${VERSION}.zip"

echo "Version: ${VERSION}"
echo ""

echo "Cleaning previous build..."
rm -f "${PACKAGE_NAME}"
rm -f "../../dev/releases/${PACKAGE_NAME}"

echo "Creating installation package..."
zip -9 -r "${PACKAGE_NAME}" . \
    -x "*.git*" \
    -x "*test*" \
    -x "*Test*" \
    -x "*.zip" \
    -x "build.sh" \
    -x "MANUAL_INSTALL.md" \
    -x ".DS_Store" \
    -x "phpunit.xml*" \
    -q

if [ -f "${PACKAGE_NAME}" ]; then
    SIZE=$(du -h "${PACKAGE_NAME}" | cut -f1)
    echo ""
    echo "Build successful"
    echo "Package: ${PACKAGE_NAME}"
    echo "Size: ${SIZE}"
    
    if [ -d "../../dev/releases" ]; then
        cp "${PACKAGE_NAME}" "../../dev/releases/"
        echo "Copied to: ../../dev/releases/${PACKAGE_NAME}"
    fi
    echo ""
else
    echo "Build failed"
    exit 1
fi

echo "========================================="
echo "Build complete"
echo "========================================="
