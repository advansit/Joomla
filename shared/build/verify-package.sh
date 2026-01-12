#!/bin/bash
#
# Generic Package Verification Script
# Usage: Run from extension directory
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check if build.env exists
if [ ! -f "build.env" ]; then
    echo -e "${RED}Error: build.env not found${NC}"
    exit 1
fi

source build.env

PACKAGE_NAME="${PACKAGE_NAME:-${EXTENSION_NAME}_${VERSION}.zip}"

if [ ! -f "$PACKAGE_NAME" ]; then
    echo -e "${RED}Error: Package $PACKAGE_NAME not found${NC}"
    exit 1
fi

echo -e "${BLUE}=========================================${NC}"
echo -e "${BLUE}Package Verification${NC}"
echo -e "${BLUE}=========================================${NC}"
echo ""

# List package contents
echo "Package contents:"
unzip -l "$PACKAGE_NAME"

echo ""
echo -e "${GREEN}Package verification complete${NC}"
