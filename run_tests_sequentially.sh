#!/bin/bash

# Configuration
TEST_DIR="tests"
PHPUNIT="./vendor/bin/phpunit"

# Check if phpunit exists
if [ ! -f "$PHPUNIT" ]; then
    echo "Error: phpunit not found at $PHPUNIT"
    exit 1
fi

# Get all test files
TEST_FILES=$(find "$TEST_DIR" -name "*Test.php" -not -path "*/vendor/*")

TOTAL_TESTS=$(echo "$TEST_FILES" | wc -l | xargs)
PASSED=0
FAILED=0
FAILED_FILES=()

echo "Starting sequential test run ($TOTAL_TESTS files)..."
echo "------------------------------------------------"

COUNTER=0
for FILE in $TEST_FILES; do
    COUNTER=$((COUNTER + 1))
    echo "[$COUNTER/$TOTAL_TESTS] Running $FILE..."
    
    # Run the test
    $PHPUNIT "$FILE"
    
    if [ $? -eq 0 ]; then
        PASSED=$((PASSED + 1))
    else
        FAILED=$((FAILED + 1))
        FAILED_FILES+=("$FILE")
    fi
    echo "------------------------------------------------"
done

# Summary
echo "Test Run Summary:"
echo "Total Files: $TOTAL_TESTS"
echo "Passed:      $PASSED"
echo "Failed:      $FAILED"

if [ $FAILED -gt 0 ]; then
    echo ""
    echo "Failed Test Files:"
    for FILE in "${FAILED_FILES[@]}"; do
        echo "  - $FILE"
    done
    exit 1
else
    echo ""
    echo "All tests passed!"
    exit 0
fi
