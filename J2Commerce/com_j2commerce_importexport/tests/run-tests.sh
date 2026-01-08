#!/bin/bash
#
# J2Commerce Import/Export Joomla Component - Test Runner
# Runs all automated tests in Docker environment
#

set -e

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Test results directory
RESULTS_DIR="./test-results"
mkdir -p "$RESULTS_DIR"

# Container name
CONTAINER="com_j2commerce_importexport_test"

# Function to print colored output
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

print_warning() {
    echo -e "${YELLOW}⚠️  $1${NC}"
}

# Function to run a test script
run_test() {
    local test_name=$1
    local test_script=$2
    local result_file="$RESULTS_DIR/${test_name}.txt"
    
    print_header "Running: $test_name"
    
    if docker exec "$CONTAINER" php "/var/www/html/administrator/../tests/scripts/${test_script}" > "$result_file" 2>&1; then
        cat "$result_file"
        print_success "$test_name PASSED"
        return 0
    else
        cat "$result_file"
        print_error "$test_name FAILED"
        return 1
    fi
}

# Function to copy test scripts to container
copy_test_scripts() {
    print_header "Copying test scripts to container"
    
    docker exec "$CONTAINER" mkdir -p /var/www/html/tests/scripts
    
    for script in scripts/*.php; do
        docker cp "$script" "$CONTAINER:/var/www/html/tests/scripts/"
        echo "  Copied: $(basename $script)"
    done
    
    print_success "Test scripts copied"
}

# Main test execution
main() {
    local test_suite=${1:-"all"}
    local failed_tests=0
    local total_tests=0
    
    print_header "J2Commerce Import/Export Joomla Component - Test Suite"
    echo "Test Suite: $test_suite"
    echo "Container: $CONTAINER"
    echo "Results Directory: $RESULTS_DIR"
    
    # Check if container is running
    if ! docker ps | grep -q "$CONTAINER"; then
        print_error "Container $CONTAINER is not running"
        echo "Please start the test environment first:"
        echo "  cd Joomla/tests"
        echo "  docker-compose up -d"
        exit 1
    fi
    
    # Wait for Joomla to be ready
    print_header "Waiting for Joomla to be ready"
    timeout 60 bash -c 'until docker exec com_j2commerce_importexport_test curl -sf http://localhost > /dev/null 2>&1; do sleep 2; done' || {
        print_error "Joomla did not become ready in time"
        exit 1
    }
    print_success "Joomla is ready"
    
    # Install extension via HTTP
    print_header "Installing Extension"
    docker exec "$CONTAINER" php /usr/local/bin/install-extension-http.php || {
        print_error "Extension installation failed"
        exit 1
    }
    print_success "Extension installed"
    
    # Copy test scripts
    copy_test_scripts
    
    # Run tests based on suite
    case $test_suite in
        "all")
            tests=(
                "Installation:01-installation.php"
                "Frontend:02-frontend.php"
                "Backend:03-backend.php"
                "API:04-api.php"
                "Database:05-database.php"
                "J2Store:06-j2commerce.php"
                "Multilingual:08-multilingual.php"
                "Security:09-security.php"
                "Performance:10-performance.php"
                "Uninstall:07-uninstall.php"  # Run last - uninstalls component
            )
            ;;
        "installation")
            tests=("Installation:01-installation.php")
            ;;
        "frontend")
            tests=("Frontend:02-frontend.php")
            ;;
        "backend")
            tests=("Backend:03-backend.php")
            ;;
        "api")
            tests=("API:04-api.php")
            ;;
        "database")
            tests=("Database:05-database.php")
            ;;
        "j2commerce")
            tests=("J2Store:06-j2commerce.php")
            ;;
        "uninstall")
            tests=("Uninstall:07-uninstall.php")
            ;;
        "multilingual")
            tests=("Multilingual:08-multilingual.php")
            ;;
        "security")
            tests=("Security:09-security.php")
            ;;
        "performance")
            tests=("Performance:10-performance.php")
            ;;
        *)
            print_error "Unknown test suite: $test_suite"
            echo "Available suites: all, installation, frontend, backend, api, database, j2commerce, uninstall, multilingual, security, performance"
            exit 1
            ;;
    esac
    
    # Run each test
    for test in "${tests[@]}"; do
        IFS=':' read -r name script <<< "$test"
        total_tests=$((total_tests + 1))
        
        if ! run_test "$name" "$script"; then
            failed_tests=$((failed_tests + 1))
        fi
    done
    
    # Print summary
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

# Run main function
main "$@"
