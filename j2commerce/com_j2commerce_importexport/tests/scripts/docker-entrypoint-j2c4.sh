#!/bin/bash
set -e
# Install J2Commerce 4 before the extension, then run the standard entrypoint.
export J2COMMERCE_ZIP=/tmp/j2commerce.zip
export J2COMMERCE_VERSION=4
exec /usr/local/bin/docker-entrypoint-j2c-base.sh "$@"
