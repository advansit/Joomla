#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
BLUE='\033[0;34m'
NC='\033[0m'

RESULTS_DIR="./test-results"
mkdir -p "$RESULTS_DIR"
CONTAINER="plg_j2commerce_acymailing_test"

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() { echo -e "${GREEN}✅ $1${NC}"; }
print_error() { echo -e "${RED}❌ $1${NC}"; }

run_test() {
    local test_name=$1
    local test_script=$2
    local result_file="$RESULTS_DIR/${test_name}.txt"
    
    print_header "Running: $test_name"
    
    if docker exec "$CONTAINER" php "/var/www/html/tests/scripts/${test_script}" > "$result_file" 2>&1; then
        cat "$result_file"
        print_success "$test_name PASSED"
        return 0
    else
        cat "$result_file"
        print_error "$test_name FAILED"
        return 1
    fi
}

copy_test_scripts() {
    print_header "Copying test scripts to container"
    docker exec "$CONTAINER" mkdir -p /var/www/html/tests/scripts
    for script in scripts/*.php; do
        docker cp "$script" "$CONTAINER:/var/www/html/tests/scripts/"
        echo "  Copied: $(basename $script)"
    done
    print_success "Test scripts copied"
}

main() {
    local test_suite=${1:-"all"}
    local failed_tests=0
    local total_tests=0
    
    print_header "AcyMailing Integration Plugin - Test Suite"
    
    if ! docker ps | grep -q "$CONTAINER"; then
        print_error "Container $CONTAINER is not running"
        exit 1
    fi
    
    print_header "Waiting for Joomla to be ready"
    timeout 60 bash -c "until docker exec $CONTAINER curl -sf http://localhost > /dev/null 2>&1; do sleep 2; done" || {
        print_error "Joomla did not become ready in time"
        exit 1
    }
    print_success "Joomla is ready"
    
    print_header "Checking Joomla Installation"
    docker exec "$CONTAINER" ls -lh /var/www/html/configuration.php || print_error "configuration.php not found"
    docker exec "$CONTAINER" ls -lh /tmp/extension.zip || print_error "extension.zip not found"
    echo ""
    
    copy_test_scripts
    
    case $test_suite in
        "all")
            tests=(
                "Installation:01-installation.php"
                "Configuration:02-configuration.php"
                "AcyMailingIntegration:03-acymailing-integration.php"
                "EventSubscriptions:04-event-subscriptions.php"
                "SubscriptionLogic:05-subscription-logic.php"
                "ErrorHandling:06-error-handling.php"
                "Uninstall:07-uninstall.php"
            )
            ;;
        "installation")
            tests=("Installation:01-installation.php")
            ;;
        "configuration")
            tests=("Configuration:02-configuration.php")
            ;;
        "integration")
            tests=("AcyMailingIntegration:03-acymailing-integration.php")
            ;;
        "events")
            tests=("EventSubscriptions:04-event-subscriptions.php")
            ;;
        "subscription")
            tests=("SubscriptionLogic:05-subscription-logic.php")
            ;;
        "errors")
            tests=("ErrorHandling:06-error-handling.php")
            ;;
        "uninstall")
            tests=("Uninstall:07-uninstall.php")
            ;;
        *)
            print_error "Unknown test suite: $test_suite"
            echo "Available suites: all, installation, configuration, integration, events, subscription, errors, uninstall"
            exit 1
            ;;
    esac
    
    for test in "${tests[@]}"; do
        IFS=':' read -r name script <<< "$test"
        total_tests=$((total_tests + 1))
        if ! run_test "$name" "$script"; then
            failed_tests=$((failed_tests + 1))
        fi
    done
    
    print_header "Test Summary"
    echo "Total Tests: $total_tests"
    echo "Passed: $((total_tests - failed_tests))"
    echo "Failed: $failed_tests"
    
    if [ $failed_tests -eq 0 ]; then
        print_success "All tests passed!"
        exit 0
    else
        print_error "$failed_tests test(s) failed"
        exit 1
    fi
}

main "$@"
