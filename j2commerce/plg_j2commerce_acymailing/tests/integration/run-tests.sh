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
        if curl -sf http://localhost:8080 > /dev/null 2>&1; then
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
    
    if docker exec j2commerce_acymailing_test test -f /tmp/extension.zip; then
        log_test "installation" "Package exists" "PASS" "Extension package found"
    else
        log_test "installation" "Package exists" "FAIL" "Extension package not found"
        return 1
    fi
    
    if docker exec j2commerce_acymailing_test test -d /var/www/html/plugins/j2store/acymailing; then
        log_test "installation" "Extension installed" "PASS" "Plugin directory exists"
    else
        log_test "installation" "Extension installed" "FAIL" "Plugin directory not found"
        return 1
    fi
    
    if docker exec j2commerce_acymailing_test test -f /var/www/html/plugins/j2store/acymailing/acymailing.xml; then
        log_test "installation" "Manifest file" "PASS" "acymailing.xml found"
    else
        log_test "installation" "Manifest file" "FAIL" "acymailing.xml not found"
        return 1
    fi
    
    return 0
}

test_activation() {
    echo -e "\n${YELLOW}=== Testing Activation ===${NC}"
    
    local enabled=$(docker exec j2commerce_acymailing_mysql mysql -u joomla -pjoomla_password joomla_db -se \
        "SELECT enabled FROM j2store_extensions WHERE element='acymailing' AND folder='j2store' LIMIT 1" 2>/dev/null || echo "0")
    
    if [ "$enabled" = "1" ]; then
        log_test "activation" "Plugin enabled" "PASS" "Plugin is enabled"
    else
        log_test "activation" "Plugin enabled" "FAIL" "Plugin not enabled"
        return 1
    fi
    
    return 0
}

test_configuration() {
    echo -e "\n${YELLOW}=== Testing Configuration ===${NC}"
    
    local params=$(docker exec j2commerce_acymailing_mysql mysql -u joomla -pjoomla_password joomla_db -se \
        "SELECT params FROM j2store_extensions WHERE element='acymailing' AND folder='j2store' LIMIT 1" 2>/dev/null || echo "")
    
    if [ -n "$params" ]; then
        log_test "configuration" "Plugin params" "PASS" "Configuration exists"
    else
        log_test "configuration" "Plugin params" "FAIL" "No configuration"
        return 1
    fi
    
    return 0
}

test_language_files() {
    echo -e "\n${YELLOW}=== Testing Language Files ===${NC}"
    
    for lang in en-CH de-CH fr-FR; do
        if docker exec j2commerce_acymailing_test test -d /var/www/html/plugins/j2store/acymailing/language/$lang; then
            log_test "language" "$lang directory" "PASS" "$lang language found"
        else
            log_test "language" "$lang directory" "FAIL" "$lang language not found"
            return 1
        fi
        
        if docker exec j2commerce_acymailing_test test -f /var/www/html/plugins/j2store/acymailing/language/$lang/plg_j2commerce_acymailing.ini; then
            log_test "language" "$lang .ini file" "PASS" "$lang .ini file found"
        else
            log_test "language" "$lang .ini file" "FAIL" "$lang .ini file not found"
            return 1
        fi
    done
    
    return 0
}

test_service_provider() {
    echo -e "\n${YELLOW}=== Testing Service Provider ===${NC}"
    
    if docker exec j2commerce_acymailing_test test -f /var/www/html/plugins/j2store/acymailing/services/provider.php; then
        log_test "service" "Provider file" "PASS" "Service provider found"
    else
        log_test "service" "Provider file" "FAIL" "Service provider not found"
        return 1
    fi
    
    return 0
}

test_extension_class() {
    echo -e "\n${YELLOW}=== Testing Extension Class ===${NC}"
    
    if docker exec j2commerce_acymailing_test test -f /var/www/html/plugins/j2store/acymailing/src/Extension/AcyMailing.php; then
        log_test "extension" "Main class" "PASS" "AcyMailing.php found"
    else
        log_test "extension" "Main class" "FAIL" "AcyMailing.php not found"
        return 1
    fi
    
    # Check for required methods in the class
    if docker exec j2commerce_acymailing_test grep -q "getSubscribedEvents" /var/www/html/plugins/j2store/acymailing/src/Extension/AcyMailing.php; then
        log_test "extension" "Event subscription" "PASS" "getSubscribedEvents method found"
    else
        log_test "extension" "Event subscription" "FAIL" "getSubscribedEvents method not found"
        return 1
    fi
    
    return 0
}

main() {
    echo -e "${YELLOW}Starting Integration Tests for J2Commerce AcyMailing Plugin${NC}\n"
    
    docker-compose up -d
    
    if ! wait_for_joomla; then
        docker-compose logs
        exit 1
    fi
    
    local failed=0
    
    test_installation || failed=$((failed + 1))
    test_activation || failed=$((failed + 1))
    test_configuration || failed=$((failed + 1))
    test_language_files || failed=$((failed + 1))
    test_service_provider || failed=$((failed + 1))
    test_extension_class || failed=$((failed + 1))
    
    echo -e "\n${YELLOW}=== Test Summary ===${NC}"
    echo "Total test categories: 6"
    echo "Failed: $failed"
    echo "Passed: $((6 - failed))"
    
    cat > "$RESULTS_DIR/summary.txt" << SUMMARY
Integration Test Summary
========================
Date: $(date)
Extension: J2Commerce AcyMailing Plugin

Test Categories: 6
Passed: $((6 - failed))
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
