#!/bin/bash
set -e

RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

RESULTS_DIR="./test-results"
mkdir -p "$RESULTS_DIR"

CONTAINER="plg_privacy_j2commerce_test"

print_header() {
    echo -e "\n${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}\n"
}

print_success() {
    echo -e "${GREEN}✅ $1${NC}"
}

print_error() {
    echo -e "${RED}❌ $1${NC}"
}

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
    
    print_header "Privacy (GDPR) - Test Suite"
    
    if ! docker ps | grep -q "$CONTAINER"; then
        print_error "Container $CONTAINER is not running"
        exit 1
    fi
    
    copy_test_scripts
    
    print_header "Verifying Setup"
    if docker exec "$CONTAINER" php /var/www/html/tests/scripts/00-verify-setup.php; then
        print_success "Setup verification passed"
    else
        print_error "Setup verification failed"
        exit 1
    fi
    
    case $test_suite in
        "all")
            tests=(
                "Installation:01-installation.php"
                "Uninstall:02-uninstall.php"
            )
            ;;
        "installation")
            tests=(
                "Installation:01-installation.php"
                "Uninstall:02-uninstall.php"
            )
            ;;
        "uninstall")
            tests=("Uninstall:02-uninstall.php")
            ;;
        *)
            print_error "Unknown test suite: $test_suite"
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
