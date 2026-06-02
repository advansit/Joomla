#!/usr/bin/env bash
# Wrapper — delegates to shared test runner with J2C4 full-install container
exec ../../shared/tests/run-tests.sh "$@"
