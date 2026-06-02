#!/bin/bash
set -e
# Install J2Commerce 6 before the extension, then run the standard entrypoint.
export J2COMMERCE_ZIP=/tmp/j2commerce.zip
export J2COMMERCE_VERSION=6
exec /usr/local/bin/docker-entrypoint-j2c-base.sh "$@"
