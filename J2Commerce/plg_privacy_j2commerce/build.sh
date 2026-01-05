#!/bin/bash

# PLUGIN_NAME Build Script
# Copyright (C) 2025 Advans IT Solutions GmbH

PLUGIN_NAME="plg_privacy_j2commerce"

echo "========================================="
echo "$PLUGIN_NAME Builder"
echo "========================================="
echo ""

if ! command -v zip &> /dev/null; then
    echo "Error: 'zip' command not found"
    exit 1
fi

SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
VERSION_FILE="${SCRIPT_DIR}/VERSION"

if [ ! -f "${VERSION_FILE}" ]; then
    echo "Error: VERSION file not found"
    exit 1
fi

VERSION=$(cat "${VERSION_FILE}" | tr -d '[:space:]')

if [ -z "${VERSION}" ]; then
    echo "Error: VERSION file is empty"
    exit 1
fi

echo "Version: ${VERSION}"
echo ""

PACKAGE_NAME="${PLUGIN_NAME}_${VERSION}.zip"
BUILD_DIR="build"
RELEASES_DIR="../../dev/releases"

echo "Cleaning previous build..."
rm -rf "${BUILD_DIR}"
rm -f "${PACKAGE_NAME}"

echo "Creating build directory..."
mkdir -p "${BUILD_DIR}"

echo "Copying files..."
rsync -av \
    --exclude='tests' \
    --exclude='test' \
    --exclude='build' \
    --exclude='*.zip' \
    --exclude='.git*' \
    --exclude='build.sh' \
    --exclude='VERSION' \
    --exclude='phpunit.xml*' \
    --exclude='*.test.php' \
    --exclude='*Test.php' \
    --exclude='.DS_Store' \
    "${SCRIPT_DIR}/" "${BUILD_DIR}/"

echo "Creating installation package..."
cd "${BUILD_DIR}"
zip -9 -r "../${PACKAGE_NAME}" . -q
cd ..

if [ -f "${PACKAGE_NAME}" ]; then
    SIZE=$(du -h "${PACKAGE_NAME}" | cut -f1)
    echo ""
    echo "Build successful"
    echo "Package: ${PACKAGE_NAME}"
    echo "Size: ${SIZE}"
    
    if [ -d "${RELEASES_DIR}" ]; then
        cp "${PACKAGE_NAME}" "${RELEASES_DIR}/"
        echo "Copied to: ${RELEASES_DIR}/${PACKAGE_NAME}"
    fi
    echo ""
else
    echo "Build failed"
    exit 1
fi

echo "Cleaning build directory..."
rm -rf "${BUILD_DIR}"

echo "========================================="
echo "Build complete"
echo "========================================="
