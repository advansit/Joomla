#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m'

RESULTS_DIR="./test-results"
mkdir -p "$RESULTS_DIR"

log_test() {
    local category=$1
    local test_name=$2
    local status=$3
    local message=$4
    
    echo "[$(date '+%Y-%m-%d %H:%M:%S')] [$category] $test_name: $status - $message" >> "$RESULTS_DIR/${category}.log"
    
    if [ "$status" = "PASS" ]; then
        echo -e "${GREEN}✓${NC} $test_name"
    else
        echo -e "${RED}✗${NC} $test_name: $message"
    fi
}

wait_for_joomla() {
    echo "Waiting for Joomla..."
    local max_attempts=60
    local attempt=0
    
    while [ $attempt -lt $max_attempts ]; do
        if curl -sf http://localhost:8083 > /dev/null 2>&1; then
            echo -e "${GREEN}Joomla ready!${NC}"
            return 0
        fi
        attempt=$((attempt + 1))
        sleep 2
    done
    
    echo -e "${RED}Joomla failed to start${NC}"
    return 1
}

test_installation() {
    echo -e "\n${YELLOW}=== Testing Installation ===${NC}"
    
    if docker exec j2commerce_2fa_test test -f /tmp/extension.zip; then
        log_test "installation" "Package exists" "PASS" "Extension package found"
    else
        log_test "installation" "Package exists" "FAIL" "Extension package not found"
        return 1
    fi
    
    if docker exec j2commerce_2fa_test test -d /var/www/html/plugins/system/j2commerce_2fa; then
        log_test "installation" "Extension installed" "PASS" "Extension directory exists"
    else
        log_test "installation" "Extension installed" "FAIL" "Extension directory not found"
        return 1
    fi
    
    return 0
}

test_activation() {
    echo -e "\n${YELLOW}=== Testing Activation ===${NC}"
    
    local enabled=$(docker exec j2commerce_2fa_mysql mysql -u joomla -pjoomla_password joomla_db -se \
        "SELECT enabled FROM j2store_extensions WHERE element='j2commerce_2fa' LIMIT 1" 2>/dev/null || echo "0")
    
    if [ "$enabled" = "1" ]; then
        log_test "activation" "Extension enabled" "PASS" "Extension is enabled"
    else
        log_test "activation" "Extension enabled" "FAIL" "Extension not enabled"
        return 1
    fi
    
    return 0
}

test_configuration() {
    echo -e "\n${YELLOW}=== Testing Configuration ===${NC}"
    
    local params=$(docker exec j2commerce_2fa_mysql mysql -u joomla -pjoomla_password joomla_db -se \
        "SELECT params FROM j2store_extensions WHERE element='j2commerce_2fa' LIMIT 1" 2>/dev/null || echo "")
    
    if [ -n "$params" ]; then
        log_test "configuration" "Extension params" "PASS" "Configuration exists"
    else
        log_test "configuration" "Extension params" "FAIL" "No configuration"
        return 1
    fi
    
    return 0
}

test_functionality() {
    echo -e "\n${YELLOW}=== Testing Functionality ===${NC}"
    
    if docker exec j2commerce_2fa_test test -f /var/www/html/plugins/system/j2commerce_2fa/*.xml; then
        log_test "functionality" "Manifest file" "PASS" "Manifest found"
    else
        log_test "functionality" "Manifest file" "FAIL" "Manifest not found"
        return 1
    fi
    
    return 0
}

main() {
    echo -e "${YELLOW}Starting Integration Tests${NC}\n"
    
    docker-compose up -d
    
    if ! wait_for_joomla; then
        docker-compose logs
        exit 1
    fi
    
    local failed=0
    
    test_installation || failed=$((failed + 1))
    test_activation || failed=$((failed + 1))
    test_configuration || failed=$((failed + 1))
    test_functionality || failed=$((failed + 1))
    
    echo -e "\n${YELLOW}=== Test Summary ===${NC}"
    echo "Total: 4 categories"
    echo "Failed: $failed"
    echo "Passed: $((4 - failed))"
    
    cat > "$RESULTS_DIR/summary.txt" << SUMMARY
Integration Test Summary
========================
Date: $(date)
Extension: plg system j2commerce 2fa

Test Categories: 4
Passed: $((4 - failed))
Failed: $failed

Status: $([ $failed -eq 0 ] && echo "SUCCESS" || echo "FAILURE")
SUMMARY
    
    docker-compose down -v
    
    if [ $failed -eq 0 ]; then
        echo -e "\n${GREEN}All tests passed!${NC}"
        exit 0
    else
        echo -e "\n${RED}Some tests failed!${NC}"
        exit 1
    fi
}

main "$@"
